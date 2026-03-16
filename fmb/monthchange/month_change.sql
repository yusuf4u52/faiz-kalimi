CREATE TABLE change_table_%month% LIKE `change_table`;
INSERT INTO change_table_%month% SELECT * FROM `change_table`;
TRUNCATE TABLE change_table;

CREATE TABLE daily_thali_count_%month% LIKE `daily_thali_count`;
INSERT INTO daily_thali_count_%month% SELECT * FROM `daily_thali_count`;
TRUNCATE TABLE daily_thali_count;

CREATE TABLE fmb_roti_distribution_%month% LIKE `fmb_roti_distribution`;
INSERT INTO fmb_roti_distribution_%month% SELECT * FROM `fmb_roti_distribution`;
TRUNCATE TABLE fmb_roti_distribution;

CREATE TABLE fmb_roti_recieved_%month% LIKE `fmb_roti_recieved`;
INSERT INTO fmb_roti_recieved_%month% SELECT * FROM `fmb_roti_recieved`;
TRUNCATE TABLE fmb_roti_recieved;

CREATE TABLE menu_list_%month% LIKE `menu_list`;
INSERT INTO menu_list_%month% SELECT * FROM `menu_list`;
TRUNCATE TABLE menu_list;

CREATE TABLE receipts_%month% LIKE `receipts`;
INSERT INTO receipts_%month% SELECT * FROM `receipts`;
TRUNCATE TABLE receipts;

CREATE TABLE thalilist_%month% LIKE thalilist;
ALTER TABLE thalilist_%month% MODIFY COLUMN `Total_Pending` int(11);
INSERT INTO thalilist_%month% SELECT * FROM `thalilist`;
ALTER TABLE thalilist_%month% MODIFY COLUMN `Total_Pending` int(11) GENERATED ALWAYS AS (`Previous_Due` + `yearly_hub` + `Zabihat` - `Paid`) STORED;
UPDATE thalilist SET Previous_Due = Total_Pending;
UPDATE thalilist SET previous_hub = yearly_hub;
UPDATE thalilist SET yearly_hub = 0, Zabihat = 0, Paid = 0, thalicount = 0;

CREATE TABLE transporter_daily_count_%month% LIKE `transporter_daily_count`;
INSERT INTO transporter_daily_count_%month% SELECT * FROM `transporter_daily_count`;
TRUNCATE TABLE transporter_daily_count;

CREATE TABLE user_feedmenu_%month% LIKE `user_feedmenu`;
INSERT INTO user_feedmenu_%month% SELECT * FROM `user_feedmenu`;
TRUNCATE TABLE user_feedmenu;

CREATE TABLE user_menu_%month% LIKE `user_menu`;
INSERT INTO user_menu_%month% SELECT * FROM `user_menu`;
TRUNCATE TABLE user_menu;

CREATE TABLE zabihat_%month% LIKE `zabihat`;
INSERT INTO zabihat_%month% SELECT * FROM `zabihat`;
TRUNCATE TABLE zabihat;

UPDATE settings SET `value` = `value` + 1 WHERE `settings`.`key` = 'current_year';
INSERT INTO settings (`key`,`value`) values ('cash_in_hand_%month%',0);