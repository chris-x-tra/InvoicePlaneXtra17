alter table ip_clients modify client_address_1 varchar(255), 
modify client_address_2 varchar(255),
modify  client_city varchar(100),
modify  client_zip varchar(20),
modify  client_state varchar(255),
modify  client_country varchar(100),
modify  client_phone varchar(255),
modify  client_fax varchar(255),
modify  client_mobile varchar(255), 
modify client_email varchar(255), 
modify client_web varchar(255),
modify  client_vat_id varchar(255),
modify  client_tax_code varchar(255),
modify client_name varchar(255);

alter table ip_clients 
add column delivery_salutation varchar(255), 
add column delivery_contact_person varchar(255), 
add column delivery_name varchar(255), 
add column delivery_name2 varchar(255), 
add column delivery_address_1 varchar(255), 
add column delivery_address_2 varchar(255), 
add column delivery_city varchar(100), 
add column delivery_zip varchar(20), 
add column delivery_state varchar(255), 
add column delivery_country varchar(255);

alter table ip_clients 
add column invoice_salutation varchar(255), 
add column invoice_contact_person varchar(255), 
add column invoice_name varchar(255), 
add column invoice_name2 varchar(255), 
add column invoice_address_1 varchar(255), 
add column invoice_address_2 varchar(255), 
add column invoice_city varchar(100), 
add column invoice_zip varchar(20), 
add column invoice_state varchar(255), 
add column invoice_country varchar(255);

alter table ip_clients add client_salutation varchar(255) after client_date_modified;
alter table ip_clients add client_contact_person varchar(255) after client_salutation;

create table ip_client_extended (
  client_extended_id int auto_increment primary key,
  client_id int,

  client_type int(1) default 1,
  client_flags int,
  customer_no varchar(255),

  contract varchar(255),
  direct_debit varchar(255),
  bank_name varchar(255),
  bank_bic varchar(255),
  bank_iban varchar(255),
  payment_terms text,
  delivery_terms text,

  carelevel int(1),
  carelevel_since date,
  health_insurance_number varchar(255),
  memo text
);

alter table ip_client_notes add client_note_timestamp datetime;

create table ip_documents (document_id int auto_increment primary key,
  client_id int, document_filename varchar(255), document_description varchar(255),
  document_deleted int default 0, document_created datetime );


create table ip_expenses (
expense_id int auto_increment primary key not null,
expense_number varchar(100),
expense_description varchar(255),
expense_status_id tinyint,
expense_category_id int,
expense_supplier_id int,
expense_date date,
expense_due_date date,
expense_amount decimal(20,2),

expense_paid_date date,
expense_bank_book_date date,
expense_bank_book_subject varchar(100),
expense_date_created datetime,
expense_date_modified datetime
);

create table ip_expenses_documents (document_id int auto_increment primary key,
  expenses_id int, document_filename varchar(255), document_description varchar(255),
  document_deleted int default 0, document_created datetime );

alter table ip_payments 
add payment_bank_book_date     date,
add payment_bank_book_subject  varchar(100),
add payment_date_created       datetime ,
add payment_date_modified      datetime ;

alter table ip_clients 
add invoice_phone varchar(255) after invoice_country, 
add invoice_email varchar(255) after invoice_phone, 
add delivery_phone varchar(255) after invoice_country,
add delivery_email varchar(255) after delivery_phone;

alter table ip_invoices add invoice_class tinyint(2) after client_id;
alter table ip_invoices add invoice_type tinyint(2) after invoice_class;
alter table ip_quotes add quote_class tinyint(2) after client_id;
alter table ip_quotes add quote_type tinyint(2) after quote_class;

CREATE TABLE ip_number_sequences (
    number_sequence_id INT AUTO_INCREMENT PRIMARY KEY,
    number_sequence_name VARCHAR(255),
    number_sequence_identifier_format VARCHAR(255),
    number_sequence_next_id INT(11),
    number_sequence_left_pad INT(2)
) ;

INSERT INTO `ip_number_sequences` VALUES
(1,'Clients Number Sequence','{{{id}}}',1,0),
(2,'Supplier Number Sequence','{{{id}}}',1,0);

insert into ip_settings (setting_key, setting_value) values ('pdf_stamp_quote', '');
insert into ip_settings (setting_key, setting_value) values ('pdf_stamp_invoice', '');

insert into ip_settings (setting_key, setting_value) values ('invoice_filename', '');
insert into ip_settings (setting_key, setting_value) values ('quote_filename', '');

insert into ip_settings (setting_key, setting_value) values ('stream_pdf', '1');
insert into ip_settings (setting_key, setting_value) values ('invoice_copy', '0');
insert into ip_settings (setting_key, setting_value) values ('invoice_copy_watermark', '');
insert into ip_settings (setting_key, setting_value) values ('invoice_pdf3a', '0');
insert into ip_settings (setting_key, setting_value) values ('invoice_pdf3a', '0');
insert into ip_settings (setting_key, setting_value) values ('invoice_nr_page_on_footer', '0');
insert into ip_settings (setting_key, setting_value) values ('invoice_nr_page_on_footer_text', '');
insert into ip_settings (setting_key, setting_value) values ('invoice_quote_options_buttons', '0');
insert into ip_settings (setting_key, setting_value) values ('client_infinite_scroll', '0');

--
-- check collate of all tables: ALTER TABLE ip_number_sequences CONVERT TO CHARACTER SET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci;
--

--
-- important indexes for massive speed b00st! and i say massive! by chrissie
-- clients
CREATE INDEX idx_clients_active 
  ON ip_clients (client_active);
CREATE INDEX idx_invoices_client_id
  ON ip_invoices (client_id);
CREATE INDEX idx_invoice_amounts_invoice_id
  ON ip_invoice_amounts (invoice_id);
CREATE INDEX idx_client_extended_client_id
  ON ip_client_extended (client_id);
CREATE INDEX idx_client_extended_client_type
  ON ip_client_extended (client_type);

-- Invoices
-- Clients (JOIN)
CREATE INDEX idx_clients_id ON ip_clients (client_id);
-- Client Extended (JOIN)
CREATE INDEX idx_client_extended_client_id
ON ip_client_extended (client_id);
-- Invoices (wichtig!)
CREATE INDEX idx_invoices_user_id
ON ip_invoices (user_id);
-- Recurring (für Subquery!)
CREATE INDEX idx_invoices_recurring_invoice_id
ON ip_invoices_recurring (invoice_id, recur_next_date);
-- Quotes
CREATE INDEX idx_quotes_invoice_id
ON ip_quotes (invoice_id);
-- Sumex
CREATE INDEX idx_invoice_sumex_invoice
ON ip_invoice_sumex (sumex_invoice);


CREATE TABLE `ip_timesheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timesheet_userid` int(11) DEFAULT NULL,
  `timesheet_day` int(11) DEFAULT NULL,
  `timesheet_month` int(11) DEFAULT NULL,
  `timesheet_year` int(11) DEFAULT NULL,
  `timesheet_start` time NOT NULL DEFAULT '00:00:00',
  `timesheet_end` time NOT NULL DEFAULT '00:00:00',
  `timesheet_clientid` int(11) DEFAULT NULL,
  `timesheet_remark` varchar(255) DEFAULT NULL,
  `timesheet_km` int(11) DEFAULT NULL,
  `timesheet_type` varchar(15) DEFAULT NULL,
  `timesheet_uuid` varchar(36) DEFAULT NULL,
  `timesheet_timestamp` datetime DEFAULT NULL,
  `timesheet_sync_status` enum('synced','dirty') DEFAULT 'dirty',
  `timesheet_change_status` enum('created','updated','deleted','synced') DEFAULT 'created',
  PRIMARY KEY (`id`),
  UNIQUE KEY `timesheet_uuid` (`timesheet_uuid`)
);

