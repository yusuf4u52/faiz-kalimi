<?php

declare(strict_types=1);

namespace JamaatReport;

/**
 * Talks to punekalimi.jamaatonline.in: logs in via the plain ASP.NET WebForms
 * postback (no headless browser needed), then reuses the resulting session
 * cookies to call the site's public JamaatOnline.asmx SOAP service directly
 * over cURL (the `soap` PHP extension isn't assumed to be available on the
 * shared host, so requests/responses are built and parsed by hand).
 */
class JamaatOnlineClient
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $cookieJar;
    private bool $loggedIn = false;

    public function __construct(string $baseUrl, string $username, string $password)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'joc_');

        // Every *DueReport()/*Roster() method below locates its <tbody> with a
        // non-greedy DOTALL regex. PHP's default pcre.backtrack_limit (1,000,000)
        // is comfortably enough for a single member's filtered response, but not
        // for a jamaat-wide unfiltered one: confirmed getSabeelDueReport('', '')
        // against the full ~1180-row report (a ~2.6MB response body) blows the
        // default limit and preg_match() silently returns no match — no warning,
        // no exception, just an empty array back to the caller. Raised here so
        // every regex this class runs gets the same headroom, not just this one
        // call site.
        ini_set('pcre.backtrack_limit', '20000000');
    }

    public function __destruct()
    {
        if (is_file($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }

    public function login(): void
    {
        $getResp = $this->curl('GET', $this->baseUrl . '/');
        $html = $getResp['body'];

        $viewState = $this->extractHiddenField($html, '__VIEWSTATE');
        $viewStateGenerator = $this->extractHiddenField($html, '__VIEWSTATEGENERATOR');
        $jamaatId = $this->extractHiddenField($html, 'HiddenJamaatID');

        if ($viewState === null) {
            throw new \RuntimeException('Could not find __VIEWSTATE on login page; page layout may have changed.');
        }

        $postFields = [
            '__EVENTTARGET' => 'LinkButtonLogin',
            '__EVENTARGUMENT' => '',
            '__VIEWSTATE' => $viewState,
            '__VIEWSTATEGENERATOR' => $viewStateGenerator ?? '',
            'HiddenJamaatID' => $jamaatId ?? '',
            'TextBoxUserName' => $this->username,
            'TextBoxPassword' => $this->password,
        ];

        $postResp = $this->curl('POST', $this->baseUrl . '/', $postFields);

        // A successful login redirects (302) to FiscalYear_Login.aspx.
        // A failed login re-renders the same page (200) with an error message.
        if ($postResp['status'] !== 302) {
            throw new \RuntimeException(
                'JamaatOnline login failed (expected 302 redirect, got HTTP ' . $postResp['status'] . '). '
                . 'Check the configured username/password.'
            );
        }

        $this->loggedIn = true;
    }

    /**
     * Reads the fiscal-year dropdown on FiscalYear_Login.aspx and returns
     * [id => label], e.g. [20 => '01 Apr 2026 To 31 Mar 2027'].
     * Scraped live each run so newly added years are picked up automatically.
     *
     * @return array<int, string>
     */
    public function getFiscalYears(): array
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/FiscalYear_Login.aspx');
        $html = $resp['body'];

        $years = [];
        if (preg_match('/<select[^>]*name="ddlfiscalyear"[^>]*>(.*?)<\/select>/is', $html, $selectMatch)) {
            if (preg_match_all('/<option[^>]*value="(\d+)"[^>]*>([^<]*)</i', $selectMatch[1], $optionMatches, PREG_SET_ORDER)) {
                foreach ($optionMatches as $opt) {
                    $years[(int) $opt[1]] = trim($opt[2]);
                }
            }
        }

        if ($years === []) {
            throw new \RuntimeException('Could not find any fiscal years in ddlfiscalyear on FiscalYear_Login.aspx.');
        }

        return $years;
    }

    /**
     * Calls a JamaatOnline.asmx SOAP operation and returns the raw string
     * result (the *Result element's text content — the service wraps every
     * response as a plain string, whose actual JSON/XML shape varies by
     * operation and is inspected separately, see scripts/explore.php).
     *
     * @param array<string, scalar> $params
     */
    public function callOperation(string $operation, array $params = []): string
    {
        $this->assertLoggedIn();

        $paramXml = '';
        foreach ($params as $name => $value) {
            $paramXml .= sprintf('<%s>%s</%s>', $name, htmlspecialchars((string) $value, ENT_XML1), $name);
        }

        $envelope = '<?xml version="1.0" encoding="utf-8"?>'
            . '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
            . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" '
            . 'xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<soap:Body>'
            . '<' . $operation . ' xmlns="http://tempuri.org/">' . $paramXml . '</' . $operation . '>'
            . '</soap:Body>'
            . '</soap:Envelope>';

        $resp = $this->curl(
            'POST',
            $this->baseUrl . '/JamaatOnline.asmx',
            null,
            [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://tempuri.org/' . $operation . '"',
            ],
            $envelope
        );

        if ($resp['status'] !== 200) {
            throw new \RuntimeException("SOAP call to $operation failed with HTTP {$resp['status']}: {$resp['body']}");
        }

        $resultTag = $operation . 'Result';
        if (preg_match('/<' . preg_quote($resultTag, '/') . '[^>]*>(.*?)<\/' . preg_quote($resultTag, '/') . '>/is', $resp['body'], $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_XML1);
        }

        // Some operations (e.g. GetMumineenDetailByID) return an untyped/complex
        // result rather than a plain string — hand back the raw SOAP body so the
        // caller can inspect it.
        return $resp['body'];
    }

    /**
     * Completes the second half of login: FiscalYear_Login.aspx must be
     * submitted (selecting a year) before the session is fully initialized —
     * confirmed required for family-lookup pages (MumineenProfile.aspx 500s
     * without it) even though some SOAP calls work without this step.
     */
    public function selectFiscalYear(int $yearId): void
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/FiscalYear_Login.aspx');
        $fields = HtmlForm::extractFields($resp['body']);
        $fields['__EVENTTARGET'] = 'LinkButtonLogin';
        $fields['__EVENTARGUMENT'] = '';
        $fields['ddlfiscalyear'] = (string) $yearId;

        $postResp = $this->curl('POST', $this->baseUrl . '/FiscalYear_Login.aspx', $fields);
        if ($postResp['status'] !== 302) {
            throw new \RuntimeException(
                "Fiscal year selection failed (expected 302 redirect, got HTTP {$postResp['status']})."
            );
        }
    }

    /**
     * Calls a JamaatOnline.asmx operation via the JSON ScriptService calling
     * convention (POST /JamaatOnline.asmx/{operation}, application/json body)
     * rather than classic SOAP/XML. Several operations (GetSabeelMemberDetail,
     * GetSabeelPaymentDetail, GetMumineenDetailByID, checkSabeelDetail, ...)
     * crash over classic SOAP with an anonymous-type XML serialization bug in
     * the server's own code — confirmed via the *same* operations working
     * cleanly when called this way instead, since JSON serialization doesn't
     * have XmlSerializer's parameterless-constructor restriction.
     *
     * @param array<string, scalar> $params
     * @return mixed The decoded `.d` payload (shape varies by operation).
     */
    public function callJsonOperation(string $operation, array $params = [])
    {
        $this->assertLoggedIn();

        $resp = $this->curl(
            'POST',
            $this->baseUrl . '/JamaatOnline.asmx/' . $operation,
            null,
            ['Content-Type: application/json; charset=utf-8'],
            json_encode($params, JSON_THROW_ON_ERROR)
        );

        if ($resp['status'] !== 200) {
            throw new \RuntimeException("JSON call to $operation failed with HTTP {$resp['status']}: {$resp['body']}");
        }

        $decoded = json_decode($resp['body'], true);
        if (!is_array($decoded) || !array_key_exists('d', $decoded)) {
            throw new \RuntimeException("JSON call to $operation returned an unexpected shape: {$resp['body']}");
        }

        return $decoded['d'];
    }

    /**
     * Returns every member the Sabeel search grid can list, keyed by Member ID —
     * used to discover which members hold their own Sabeel account. With every
     * filter left blank this returns the whole jamaat roster; passing $itsNo or
     * $sabeelNo narrows the search server-side (much cheaper than scanning the
     * full roster client-side when only one member is wanted). The site's own
     * rendering repeats each row several times (confirmed: querying a single
     * known SabeelNo already came back with the same member 4x), so rows are
     * deduped by Member ID as they're parsed.
     *
     * @return array<int, array{itsNo: string, fullName: string}>
     */
    public function getSabeelMemberRoster(string $itsNo = '', string $sabeelNo = ''): array
    {
        $html = $this->callOperation('GetSabeelMember', [
            'Gender' => '', 'FirstName' => '', 'LastName' => '', 'ITSNo' => $itsNo, 'SabeelNo' => $sabeelNo,
        ]);

        $roster = [];
        preg_match_all(
            '/value="(\d+)"\s+onclick="javascript:SabeelClick\(this,\d+\);"[^>]*\/><\/td><td>\d+<\/td><td class="center">(\d+)<\/td><td>([^<]*)<\/td>/',
            $html,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $m) {
            $roster[(int) $m[1]] = [
                'itsNo' => $m[2],
                'fullName' => trim(html_entity_decode($m[3], ENT_QUOTES)),
            ];
        }

        return $roster;
    }

    /**
     * Fetches a member's profile fields (name, mobile, email, address, ...) via
     * GetMumineenDetailByID's JSON ScriptService call. The operation's top-level
     * FullName field is always blank — confirmed the real data only lives in the
     * "Details" value, a flat "<p>Label :- value</p>" fragment list rather than a
     * proper structured payload.
     *
     * @return array<string, string> Keyed by lowercased label with spaces stripped, e.g. 'fullname', 'email', 'mobile'.
     */
    public function getMumineenDetail(int $memberId): array
    {
        $result = $this->callJsonOperation('GetMumineenDetailByID', ['ID' => $memberId]);
        $details = is_array($result) ? ($result['Details'] ?? '') : '';

        $fields = [];
        preg_match_all('/<p>([^:]+?)\s*:-\s*([^<]*)<\/p>/', (string) $details, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $key = strtolower(str_replace(' ', '', trim($m[1])));
            $fields[$key] = trim(html_entity_decode($m[2], ENT_QUOTES));
        }

        return $fields;
    }

    /**
     * Scrapes SabeelDueReport.aspx — the jamaat office's own authoritative Sabeel
     * dues report — instead of reconstructing outstanding balances from raw
     * per-record ledger calls. That approach was tried first (GetSelectedSabeelMemberDetail
     * + GetSabeelDetail) and confirmed unreliable: GetSabeelDetail only sees the
     * *current* SabeelDetailID's own payment history, missing payments recorded
     * against a now-superseded record from before a grade change (e.g. Sabeel 304's
     * House Sabeel showed a false "due" via that path when the member was actually
     * 2390 in advance — the real advance payment was on record 183, not the current
     * record 1397 that path queried).
     *
     * Requires a login with report-viewing permission — the plain `cronuser` login
     * used for every other operation in this class gets redirected to
     * /Jamaat/Unauthorized.aspx here.
     *
     * With every filter left blank and $includeCredits true, this returns every
     * Sabeel-type line for every member jamaat-wide in one response (confirmed://
     * ~1180 rows, not paginated). The site's own UI hides negative-due (advance/credit)
     * lines unless "All Records (with negative due)" is explicitly selected — pass
     * $includeCredits = true (the default) to see those too; outstanding is negative
     * for an advance/credit, positive when due.
     *
     * @return array<int, array{itsNo: string, fullName: string, mohallaName: string, sabeelType: string, grade: string, sabeelAmount: string, sabeelNo: string, mobile: string, paidTill: string, outstanding: float}>
     */
    public function getSabeelDueReport(string $sabeelNo = '', string $itsNo = '', bool $includeCredits = true): array
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/Jamaat/SabeelDueReport.aspx');
        $fields = HtmlForm::extractFields($resp['body']);
        $fields['ctl00$ctl00$JamaatContent$MainContent$txtsabeelno'] = $sabeelNo;
        $fields['ctl00$ctl00$JamaatContent$MainContent$TextBoxITSNo'] = $itsNo;
        $fields['ctl00$ctl00$JamaatContent$MainContent$ddlfilter'] = $includeCredits ? 'All' : '';
        $fields['ctl00$ctl00$JamaatContent$MainContent$ButtonSearch'] = 'Search';

        $searchResp = $this->curl('POST', $this->baseUrl . '/Jamaat/SabeelDueReport.aspx', $fields);
        if ($searchResp['status'] !== 200) {
            throw new \RuntimeException("SabeelDueReport.aspx search failed with HTTP {$searchResp['status']}.");
        }

        if (!preg_match('/id="[^"]*ReportList[^"]*"[^>]*>.*?<tbody>(.*?)<\/tbody>/is', $searchResp['body'], $tbodyMatch)) {
            return [];
        }

        $rows = [];
        foreach ($this->extractTableRows($tbodyMatch[1]) as $cells) {
            // Column 0 is the row-select checkbox; the report ends with a blank
            // "TOTAL:" summary row (detected via its Paid Till column) that isn't
            // a real Sabeel line.
            if (count($cells) < 12 || $cells[10] === 'TOTAL:') {
                continue;
            }

            $rows[] = [
                'itsNo' => $cells[2],
                'fullName' => $cells[3],
                'mohallaName' => $cells[4],
                'sabeelType' => $cells[5],
                'grade' => $cells[6],
                'sabeelAmount' => $cells[7],
                'sabeelNo' => $cells[8],
                'mobile' => $cells[9],
                'paidTill' => $cells[10],
                'outstanding' => (float) str_replace(',', '', $cells[11]),
            ];
        }

        return $rows;
    }

    /**
     * Scrapes FaizYealybaseduereport.aspx ("Faiz Yearly Due Report" — Faiz is this
     * jamaat's name for what the system itself calls Niyaz). Unlike Sabeel's due
     * report, this is a running year-by-year ledger: each year's "Previous Amount"
     * is the prior year's "Due" carried forward, so the *current* amount owed is
     * the most recent year's Due — summing every year's Due would double-count
     * the carried-forward balance. Picking that latest-year row is left to the
     * caller (see SabeelReportBuilder), this method only returns the raw rows.
     *
     * Requires the same elevated login as getSabeelDueReport().
     *
     * @return array<int, array{itsNo: string, sabeelNo: string, fullName: string, mohallaName: string, takhmeenYear: string, takhmeenType: string, takhmeenAmount: float, due: float}>
     */
    public function getFaizDueReport(string $itsNo): array
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/Jamaat/FaizYealybaseduereport.aspx');
        $fields = HtmlForm::extractFields($resp['body']);
        $fields['ctl00$ctl00$JamaatContent$MainContent$txtitsno'] = $itsNo;
        $fields['ctl00$ctl00$JamaatContent$MainContent$ButtonSave'] = 'Search';

        $searchResp = $this->curl('POST', $this->baseUrl . '/Jamaat/FaizYealybaseduereport.aspx', $fields);
        if ($searchResp['status'] !== 200) {
            throw new \RuntimeException("FaizYealybaseduereport.aspx search failed with HTTP {$searchResp['status']}.");
        }

        if (!preg_match('/<tbody>(.*?)<\/tbody>/is', $searchResp['body'], $tbodyMatch)) {
            return [];
        }

        $rows = [];
        foreach ($this->extractTableRows($tbodyMatch[1]) as $cells) {
            // A real row always has an ITS No; the report's footer/empty rows don't.
            if (count($cells) < 14 || trim($cells[1]) === '') {
                continue;
            }

            $rows[] = [
                'itsNo' => $cells[1],
                'sabeelNo' => $cells[2],
                'fullName' => $cells[3],
                'mohallaName' => $cells[4],
                'takhmeenYear' => $cells[6],
                'takhmeenType' => $cells[7],
                'takhmeenAmount' => (float) str_replace(',', '', $cells[8]),
                'due' => (float) str_replace(',', '', $cells[13]),
            ];
        }

        return $rows;
    }

    /**
     * Scrapes HoobOutstandingvb.aspx ("Hoob Outstanding Report"). Unlike the Faiz
     * report, this one already lists only the Hoob campaigns ("headers") a member
     * currently has a pending balance on — confirmed: searching one specific Hoob
     * name that's fully paid off returns zero rows, so (unlike Faiz) summing every
     * returned row's due across Hoob headers is safe, they're independent pledges
     * rather than a running per-Hoob ledger. Leaving the Hoob-name filter blank
     * (the default) returns every pending Hoob header for the searched member in
     * one call — don't iterate Hoob names one by one.
     *
     * Requires the same elevated login as getSabeelDueReport(). Leaving $itsNo
     * blank to fetch jamaat-wide is NOT supported here — confirmed it times out
     * (this report is computed live per member, unlike Sabeel's due report).
     *
     * @return array<int, array{hoobName: string, takhmeenYear: string, itsNo: string, fullName: string, sabeelNo: string, mobile: string, email: string, takhmeenAmount: float, due: float}>
     */
    public function getHoobDueReport(string $itsNo): array
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/Jamaat/HoobOutstandingvb.aspx');
        $fields = HtmlForm::extractFields($resp['body']);
        $fields['ctl00$ctl00$JamaatContent$MainContent$txtitsno'] = $itsNo;
        $fields['ctl00$ctl00$JamaatContent$MainContent$ButtonSearch'] = 'Search';

        $searchResp = $this->curl('POST', $this->baseUrl . '/Jamaat/HoobOutstandingvb.aspx', $fields);
        if ($searchResp['status'] !== 200) {
            throw new \RuntimeException("HoobOutstandingvb.aspx search failed with HTTP {$searchResp['status']}.");
        }

        if (!preg_match('/<tbody>(.*?)<\/tbody>/is', $searchResp['body'], $tbodyMatch)) {
            return [];
        }

        $rows = [];
        foreach ($this->extractTableRows($tbodyMatch[1]) as $cells) {
            // A real row always has an ITS No; the footer "Total:" row doesn't.
            if (count($cells) < 12 || trim($cells[4]) === '') {
                continue;
            }

            $rows[] = [
                'hoobName' => $cells[2],
                'takhmeenYear' => $cells[3],
                'itsNo' => $cells[4],
                'fullName' => $cells[5],
                'sabeelNo' => $cells[6],
                'mobile' => $cells[7],
                'email' => $cells[8],
                'takhmeenAmount' => (float) str_replace(',', '', $cells[9]),
                'due' => (float) str_replace(',', '', $cells[10]),
            ];
        }

        return $rows;
    }

    /**
     * Reads the academic-year dropdown on MadresaFeeOutstanding.aspx and returns
     * [id => label], e.g. [8 => '2026-2027'] — same scrape-live-each-run approach
     * as getFiscalYears(); pick the current one the same way (array_key_last()),
     * since options are rendered oldest-to-newest.
     *
     * @return array<int, string>
     */
    public function getMadresaAcademicYears(): array
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/Jamaat/MadresaFeeOutstanding.aspx');
        $options = HtmlForm::extractOptionsById($resp['body'], 'JamaatContent_MainContent_ddlacadmicyear');

        $years = [];
        foreach ($options as $value => $label) {
            // PHP casts purely-numeric array keys to int, and ctype_digit()
            // reinterprets a small int argument as an ASCII code rather than a
            // number to check — cast back to string first or e.g. 8 ("2026-2027")
            // is treated as chr(8) and silently dropped.
            if (ctype_digit((string) $value)) {
                $years[(int) $value] = $label;
            }
        }

        if ($years === []) {
            throw new \RuntimeException('Could not find any academic years in ddlacadmicyear on MadresaFeeOutstanding.aspx.');
        }

        return $years;
    }

    /**
     * Returns every enrolled Madresa student and their linked HOF, keyed by the
     * student's own ITS No — via MadresaStudentEnrollmentList.aspx, which loads
     * the whole jamaat's student roster by default with no search needed
     * (confirmed ~60 rows, not paginated). This is the only place a student's ITS
     * links to their family's HOF ITS — getMadresaFeeDueReport()'s rows only have
     * the student's own details, no Sabeel/HOF reference at all.
     *
     * @return array<string, array{studentName: string, standard: string, hofItsNo: string, hofName: string, contact: string}>
     */
    public function getMadresaStudentRoster(): array
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/Jamaat/MadresaStudentEnrollmentList.aspx');
        if ($resp['status'] !== 200) {
            throw new \RuntimeException("MadresaStudentEnrollmentList.aspx failed with HTTP {$resp['status']}.");
        }

        if (!preg_match('/<tbody>(.*?)<\/tbody>/is', $resp['body'], $tbodyMatch)) {
            return [];
        }

        $roster = [];
        foreach ($this->extractTableRows($tbodyMatch[1]) as $cells) {
            // A real row always has a Student ITS No.
            if (count($cells) < 7 || trim($cells[3]) === '') {
                continue;
            }

            $roster[$cells[3]] = [
                'studentName' => $cells[2],
                'standard' => $cells[1],
                'hofItsNo' => $cells[4],
                'hofName' => $cells[5],
                'contact' => $cells[6],
            ];
        }

        return $roster;
    }

    /**
     * Scrapes MadresaFeeOutstanding.aspx ("Fee Due Report") for one academic year
     * (a required filter — see getMadresaAcademicYears()). Leaving the Standard
     * (class) filter blank returns every student's dues for that year in one call.
     * Rows are keyed by the *student's* own ITS No, not any Sabeel/HOF — cross-
     * reference with getMadresaStudentRoster() to attribute a student's due to
     * their family.
     *
     * Requires the same elevated login as getSabeelDueReport().
     *
     * @return array<int, array{studentItsNo: string, studentName: string, standard: string, totalDue: float}>
     */
    public function getMadresaFeeDueReport(int $academicYearId): array
    {
        $this->assertLoggedIn();

        $resp = $this->curl('GET', $this->baseUrl . '/Jamaat/MadresaFeeOutstanding.aspx');
        $fields = HtmlForm::extractFields($resp['body']);
        $fields['ctl00$ctl00$JamaatContent$MainContent$ddlacadmicyear'] = (string) $academicYearId;
        $fields['ctl00$ctl00$JamaatContent$MainContent$ButtonSearch'] = 'Search';

        $searchResp = $this->curl('POST', $this->baseUrl . '/Jamaat/MadresaFeeOutstanding.aspx', $fields);
        if ($searchResp['status'] !== 200) {
            throw new \RuntimeException("MadresaFeeOutstanding.aspx search failed with HTTP {$searchResp['status']}.");
        }

        if (!preg_match('/<tbody>(.*?)<\/tbody>/is', $searchResp['body'], $tbodyMatch)) {
            return [];
        }

        $rows = [];
        foreach ($this->extractTableRows($tbodyMatch[1]) as $cells) {
            // A real row always has a Student ITS No.
            if (count($cells) < 13 || trim($cells[3]) === '') {
                continue;
            }

            $rows[] = [
                'studentItsNo' => $cells[3],
                'studentName' => $cells[2],
                'standard' => $cells[4],
                'totalDue' => (float) str_replace(',', '', $cells[12]),
            ];
        }

        return $rows;
    }

    /**
     * Parses a <tbody>...</tbody> fragment's <tr> rows into per-cell text arrays
     * (tags stripped, <br> collapsed to a space, entities decoded, trimmed) — the
     * common shape of every classic-GridView report page scraped in this class.
     *
     * @return array<int, array<int, string>>
     */
    private function extractTableRows(string $tbodyHtml): array
    {
        $rows = [];
        preg_match_all('/<tr>(.*?)<\/tr>/is', $tbodyHtml, $trMatches);
        foreach ($trMatches[1] as $trHtml) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $trHtml, $tdMatches);
            $rows[] = array_map(
                // A blank-looking cell (e.g. the report's own "Total:" footer row)
                // is rendered as &nbsp;, which decodes to a non-breaking space —
                // trim() only strips ASCII whitespace, so that's normalized to a
                // regular space first or callers' "is this cell empty" checks would
                // never see it as empty.
                static fn (string $cell) => trim(str_replace(
                    "\xC2\xA0",
                    ' ',
                    html_entity_decode(strip_tags(str_replace(['<br />', '<br/>', '<br>'], ' ', $cell)), ENT_QUOTES)
                )),
                $tdMatches[1]
            );
        }

        return $rows;
    }

    /**
     * Generic authenticated GET, for pages without a dedicated method (e.g.
     * MumineenProfile.aspx, SabeelDashboard.aspx).
     *
     * @return array{status: int, body: string}
     */
    public function get(string $path): array
    {
        $this->assertLoggedIn();

        return $this->curl('GET', $this->baseUrl . $path);
    }

    /**
     * Generic authenticated classic (full-page) form POST.
     *
     * @param array<string, string> $fields
     * @return array{status: int, body: string}
     */
    public function postForm(string $path, array $fields): array
    {
        $this->assertLoggedIn();

        return $this->curl('POST', $this->baseUrl . $path, $fields);
    }

    private function assertLoggedIn(): void
    {
        if (!$this->loggedIn) {
            throw new \RuntimeException('Call login() before making SOAP calls.');
        }
    }

    private function extractHiddenField(string $html, string $id): ?string
    {
        if (preg_match('/id="' . preg_quote($id, '/') . '"\s+value="([^"]*)"/', $html, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_XML1);
        }

        return null;
    }

    /**
     * @param array<string, string>|null $postFields
     * @param string[] $extraHeaders
     * @return array{status: int, body: string}
     */
    private function curl(string $method, string $url, ?array $postFields = null, array $extraHeaders = [], ?string $rawBody = null): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (jamaat-pledge-report)',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $extraHeaders,
        ]);

        // Local XAMPP installs commonly ship without a CA bundle wired up.
        // If we've vendored one for local dev, use it; production LAMP hosts
        // normally have valid system CA certs already, so this is a no-op there.
        $localCaBundle = __DIR__ . '/../config/cacert.pem';
        if (is_file($localCaBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $localCaBundle);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($rawBody !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
            } elseif ($postFields !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            }
        }

        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            throw new \RuntimeException("cURL request to $url failed: $error");
        }

        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return ['status' => $status, 'body' => $body];
    }
}
