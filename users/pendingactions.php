<?php
include('connection.php');
include('_authCheck.php');

$query = "SELECT * FROM thalilist";
$query_new_transporter = $query . " WHERE (Transporter = 'Transporter' OR sector IS NULL OR subsector IS NULL) and Thali <> '' and Thali is not null";
$result = mysqli_query($link, $query_new_transporter);
$query_new_thali = $query . " WHERE Thali is null and Active is null";
$result_new_thali = mysqli_query($link, $query_new_thali);

$transporter_list = array();
$query = "SELECT Name FROM transporters";
$result1 = mysqli_query($link, $query);
while ($values1 = mysqli_fetch_assoc($result1)) {
  $transporter_list[] = $values1['Name'];
}

$sector_list = array();
$sector_query = "SELECT DISTINCT(sector) FROM `thalilist` WHERE sector IS NOT NULL order by sector";
$sector_result = mysqli_query($link, $sector_query);
while ($sector_value = mysqli_fetch_assoc($sector_result)) {
  $sector_list[] = $sector_value['sector'];
}

$subsector_list = array();
$subsector_query = "SELECT DISTINCT(subsector) FROM `thalilist` WHERE subsector IS NOT NULL order by subsector";
$subsector_result = mysqli_query($link, $subsector_query);
while ($subsector_value = mysqli_fetch_assoc($subsector_result)) {
  $subsector_list[] = $subsector_value['subsector'];
}
?>
<!DOCTYPE html>
<!-- saved from url=(0029)http://bootswatch.com/flatly/ -->
<html lang="en">

<head>
  <?php include('_head.php'); ?>
</head>

<body>
  <?php include('_nav.php'); ?>

  <div class="container">
    <!-- Forms
      ================================================== -->
    <div class="row">
      <div class="row">
        <div class="col-lg-12">

          <div class="col-lg-12">
            <div class="page-header">
              <h2 id="tables">Transporter request</h2>
            </div>
            <div class="bs-component">
              <table class="table table-striped table-hover ">
                <thead>
                  <tr>
                    <th>Thali No</th>
                    <th>Transporter</th>
                    <th>Sector</th>
                    <th>Subsector</th>
                    <th>Address</th>
                    <th>Name</th>
                    <th>Active</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  while ($values = mysqli_fetch_assoc($result)) {
                  ?>
                    <form action='savetransporter.php' method='post'>
                      <tr>

                        <td>
                          <?php echo $values['Thali']; ?>
                          <input type="hidden" name="Thali" value="<?php echo $values['Thali']; ?>">
                        </td>
                        <td>
                        <?php
                        if ($values['yearly_hub'] != "0") {
                        ?>
                          <select class='transporter' name='transporter' required>
                            <option value=''>Select</option>
                            <?php
                            foreach ($transporter_list as $tname) {
                            ?>
                              <option value='<?php echo $tname; ?>' <?php echo ($tname == $values['Transporter']) ? 'selected' : ''; ?>><?php echo $tname; ?></option>
                            <?php
                            }
                            ?>
                          </select>
                          <?php } ?>
                        </td>
                        <td>
                          <select class='sector' name='sector' required>
                            <option value=''>Select</option>
                            <?php
                            foreach ($sector_list as $sector_name) {
                            ?>
                              <option value='<?php echo $sector_name; ?>' <?php echo ($sector_name == $values['sector']) ? 'selected' : ''; ?>><?php echo $sector_name; ?></option>
                            <?php
                            }
                            ?>
                          </select>
                        </td>
                        <td>
                          <select class='subsector' name='subsector' required>
                            <option value=''>Select</option>
                            <?php
                            foreach ($subsector_list as $subsector_name) {
                            ?>
                              <option value='<?php echo $subsector_name; ?>' <?php echo ($subsector_name == $values['subsector']) ? 'selected' : ''; ?>><?php echo $subsector_name; ?></option>
                            <?php
                            }
                            ?>
                          </select>
                        </td>
                        <td><?php echo $values['Full_Address']; ?></td>
                        <td><?php echo $values['NAME']; ?></td>
                        <td><?php echo ($values['Active'] == '1') ? 'Yes' : 'No'; ?></td>
                        <td><input type='submit' value='Submit'></td>
                      </tr>
                    </form>
                  <?php } ?>
                </tbody>
              </table>
            </div><!-- /example -->

          </div>

          <div class="col-lg-12">
            <div class="page-header">
              <h2 id="tables">New Thali</h2>
            </div>
            <?php
            $sql = mysqli_query($link, "
            SELECT (
t1.Thali +1
) AS gap_starts_at, (
SELECT MIN( t3.Thali ) -1
FROM thalilist t3
WHERE t3.Thali > t1.Thali
) AS gap_ends_at
FROM thalilist t1
WHERE NOT 
EXISTS (
SELECT t2.Thali
FROM thalilist t2
WHERE t2.Thali = t1.Thali +1
)
HAVING gap_ends_at IS NOT NULL 
LIMIT 0 , 30");
            $row = mysqli_fetch_row($sql);
            $plusone = $row[0];
            echo "Thali No. :: $plusone  can be given";
            ?>
            <div class="bs-component">
              <table class="table table-striped table-hover ">
                <thead>
                  <tr>
                    <th>Thali No</th>
                    <th>Transporter</th>
                    <th>Musaid</th>
                    <th>Hub</th>
                    <th>Address</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  while ($values = mysqli_fetch_assoc($result_new_thali)) {
                  ?>
                    <form action='activatethali.php' method='post'>
                      <tr>

                        <td>
                          <input type='hidden' value='<?php echo $values['Email_ID']; ?>' name='email'>
                          <input type='hidden' value='<?php echo $values['id']; ?>' name='id'>
                          <input type='hidden' value='<?php echo $values['NAME']; ?>' name='name'>
                          <input type='hidden' value='<?php echo $values['CONTACT']; ?>' name='contact'>
                          <input type='hidden' value='<?php echo $values['Full_Address']; ?>' name='address'>
                          <input type='hidden' value='<?php echo $values['Transporter']; ?>' name='trasnporter'>
                          <input type='text' size=8 name='thalino' class='' required='required'>
                        </td>
                        <td>
                          <?php if ($values['Transporter'] == 'Transporter') { ?>
                            <select name="transporter" required='required'>
                              <option value=''>Select</option>
                              <?php
                              foreach ($transporter_list as $tname) {
                              ?>
                                <option value='<?php echo $tname; ?>'><?php echo $tname; ?></option>
                              <?php
                              }
                              ?>
                            </select>
                          <?php } else {
                            echo "Pick up";
                          }
                          ?>
                        </td>
                        <td>
                          <select name="musaid" required='required'>
                            <option value=''>Select</option>
                            <?php
                            $musaid_list = mysqli_query($link, "SELECT username, email FROM users");
                            while ($musaid = mysqli_fetch_assoc($musaid_list)) {
                            ?>
                              <option value='<?php echo $musaid['email']; ?>'><?php echo $musaid['username']; ?></option>
                            <?php
                            }
                            ?>
                          </select>
                        </td>
                        <td><input type='text' name="hub" size=8 required='required' value="<?php echo $values['yearly_hub']; ?>"></td>
                        </td>
                        <td><?php echo $values['Full_Address']; ?></td>
                        <td><?php echo $values['NAME']; ?></td>
                        <td><?php echo $values['CONTACT']; ?></td>
                        <td><input type='submit' value='Activate'></td>
                        <td><input type='submit' value='Reject' formaction="reject.php"></td>
                      </tr>
                    </form>
                  <?php } ?>
                </tbody>
              </table>
            </div><!-- /example -->

          </div>


        </div>
      </div>
    </div>
  </div>
  <?php include('_bottomJS.php'); ?>
  <script type="text/javascript">
    $(function() {
      // Handler for .ready() called.
      /* $(".transporter").change(function() {
        if (confirm('Are you sure?')) {
          $.ajax({
            type: "POST",
            url: "savetransporter.php",
            data: {
              'data': this.value
            },
            success: function(data) {
              window.location.href = window.location.href;
            }
          });
        } else {
          return false;
        }
      }); */
    });
  </script>

</body>

</html>