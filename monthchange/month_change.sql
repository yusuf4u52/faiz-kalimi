CREATE TABLE receipts_%month% LIKE `receipts`;
INSERT INTO receipts_%month% SELECT * FROM `receipts`;
TRUNCATE TABLE receipts;
CREATE TABLE sf_hisab_%month% LIKE `sf_hisab`;
INSERT INTO sf_hisab_%month% SELECT * FROM `sf_hisab`;
TRUNCATE TABLE `sf_hisab`;
CREATE TABLE account_%month% LIKE `account`;
INSERT INTO account_%month% SELECT * FROM `account`;
TRUNCATE TABLE account;
CREATE TABLE change_table_%month% LIKE `change_table`;
INSERT INTO change_table_%month% SELECT * FROM `change_table`;
TRUNCATE TABLE change_table;
CREATE TABLE amount_received_%month% LIKE `amount_received`;
INSERT INTO amount_received_%month% SELECT * FROM `amount_received`;
TRUNCATE TABLE amount_received;
CREATE TABLE thalilist_%month% LIKE thalilist;
ALTER TABLE thalilist_%month% MODIFY COLUMN `Total_Pending` int(11);
INSERT INTO thalilist_%month% SELECT * FROM `thalilist`;
ALTER TABLE thalilist_%month% MODIFY COLUMN `Total_Pending` int(11) GENERATED ALWAYS AS (`Previous_Due` + `yearly_hub` + `Zabihat` - `Paid`) STORED;
UPDATE thalilist SET Previous_Due = Total_Pending;
UPDATE thalilist SET yearly_hub = 0, Zabihat = 0, Paid = 0, thalicount = 0;
-- UPDATE thalilist SET Dues = 1800 where Active = 1;
-- UPDATE thalilist SET TranspFee = 250 where Active = 1 AND Transporter != 'Pick Up';
UPDATE settings SET `value` = `value` + 1 WHERE `settings`.`key` = 'current_year';
INSERT INTO settings (`key`,`value`) values ('cash_in_hand_%month%',0);

-- Customization

-- Sadhashive peth
-- UPDATE thalilist SET TranspFee = 350 where Active = 1 AND Transporter != 'Pick Up' AND Thali IN (9,410,602);

-- Kothrud
-- UPDATE thalilist SET TranspFee = 500 where Active = 1 AND Transporter != 'Pick Up' AND Thali IN (202,593,594,595,596,615,600,629);

-- Saif Colony
-- UPDATE thalilist SET TranspFee = 400 where Active = 1 AND Transporter != 'Pick Up' AND Thali IN (237,520);

-- Hoob adjust
-- UPDATE thalilist SET Dues = 1400 where Active = 1 AND Thali IN (553,548,618,619,620);
-- UPDATE thalilist SET Dues = 1000 where Active = 1 AND Thali IN (41);
-- UPDATE thalilist SET Dues = 1100 where Active = 1 AND Thali IN (112,598);
-- UPDATE thalilist SET Dues = 1500 where Active = 1 AND Thali IN (431);
