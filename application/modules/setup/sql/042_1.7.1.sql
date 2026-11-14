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

alter table ip_clients add client_salutation varchar(255) after client_date_modified;
alter table ip_clients add client_contact_person varchar(255) after client_salutation;

-- alter table ip_clients 
-- add column delivery_salutation varchar(255), 
-- add column delivery_contact_person varchar(255), 
-- add column delivery_name varchar(255), 
-- add column delivery_name2 varchar(255), 
-- add column delivery_address_1 varchar(255), 
-- add column delivery_address_2 varchar(255), 
-- add column delivery_city varchar(100), 
-- add column delivery_zip varchar(20), 
-- add column delivery_state varchar(255), 
-- add column delivery_country varchar(255);

-- alter table ip_clients 
-- add column invoice_salutation varchar(255), 
-- add column invoice_contact_person varchar(255), 
-- add column invoice_name varchar(255), 
-- add column invoice_name2 varchar(255), 
-- add column invoice_address_1 varchar(255), 
-- add column invoice_address_2 varchar(255), 
-- add column invoice_city varchar(100), 
-- add column invoice_zip varchar(20), 
-- add column invoice_state varchar(255), 
-- add column invoice_country varchar(255);

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
-- Recurring (f�r Subquery!)
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

alter table ip_users add user_bic varchar(64) after user_iban;
alter table ip_users add user_remittance_text varchar(255) after user_iban;
alter table ip_users add user_invoicing_contact varchar(255) after user_bic;
alter table ip_users add user_bank varchar(255) after user_subscribernumber;


---
--- neu verschiedene rechnungsadressen
---

UPDATE ip_clients SET client_birthdate='1337-01-01' WHERE client_birthdate='0000-00-00';
ALTER TABLE ip_clients ENGINE=InnoDB;
ALTER TABLE ip_clients ADD INDEX (client_id);

CREATE TABLE ip_client_addresses (
    address_id      INT NOT NULL AUTO_INCREMENT,
    client_id       INT NOT NULL,
    address_type    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    salutation      VARCHAR(255) DEFAULT NULL,
    contact_person  VARCHAR(255) DEFAULT NULL,
    name            VARCHAR(255) DEFAULT NULL,
    name2           VARCHAR(255) DEFAULT NULL,
    address_1       VARCHAR(255) DEFAULT NULL,
    address_2       VARCHAR(255) DEFAULT NULL,
    city            VARCHAR(100) DEFAULT NULL,
    zip             VARCHAR(20)  DEFAULT NULL,
    state           VARCHAR(255) DEFAULT NULL,
    country         VARCHAR(255) DEFAULT NULL,
    phone           VARCHAR(255) DEFAULT NULL,
    email           VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (address_id),
    KEY idx_client_type (client_id, address_type),
    CONSTRAINT fk_client_addresses_client
        FOREIGN KEY (client_id) REFERENCES ip_clients (client_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--- billing prefix hier um eindeutig zu sein von invoice_... !
ALTER TABLE ip_invoices
    ADD COLUMN billing_salutation     VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_contact_person VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_name           VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_name2          VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_address_1      VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_address_2      VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_city           VARCHAR(100) DEFAULT NULL,
    ADD COLUMN billing_zip            VARCHAR(20)  DEFAULT NULL,
    ADD COLUMN billing_state          VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_country        VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_phone          VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_email          VARCHAR(255) DEFAULT NULL;

ALTER TABLE ip_invoices ADD COLUMN billing_address_id INT DEFAULT NULL;

ALTER TABLE ip_quotes
    ADD COLUMN billing_salutation     VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_contact_person VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_name           VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_name2          VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_address_1      VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_address_2      VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_city           VARCHAR(100) DEFAULT NULL,
    ADD COLUMN billing_zip            VARCHAR(20)  DEFAULT NULL,
    ADD COLUMN billing_state          VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_country        VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_phone          VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_email          VARCHAR(255) DEFAULT NULL,
    ADD COLUMN billing_address_id     INT DEFAULT NULL;



---
--- anderung documents
ALTER TABLE ip_documents
    CHANGE client_id owner_id INT(11) NOT NULL,
    ADD owner_type VARCHAR(10) NOT NULL DEFAULT 'client' AFTER document_id,
    ADD document_kind VARCHAR(10) NOT NULL DEFAULT 'document' AFTER owner_type,
    ADD INDEX idx_owner (owner_type, owner_id, document_deleted);

-- Personalfragebogen: nur Felder, die in ip_users noch NICHT existieren.
-- Bereits vorhanden und wiederverwendet:
--   Name -> user_name            Strasse/Nr -> user_address_1     Wohnort -> user_city (+ user_zip)
--   Telefon -> user_phone        Handy -> user_mobile            Email -> user_email
-- Backup vorher! Alle neuen Spalten sind NULL-fähig, bestehende Zeilen bleiben unberührt.

ALTER TABLE ip_users
    ADD user_firstname              VARCHAR(100) NULL,
    ADD user_birthname              VARCHAR(100) NULL,
    ADD user_birthplace             VARCHAR(100) NULL,
    ADD user_birthdate              DATE         NULL,
    ADD user_religion               VARCHAR(50)  NULL,
    ADD user_marital_status         VARCHAR(30)  NULL,
    ADD user_children               TINYINT UNSIGNED NULL,
    ADD user_nationality            VARCHAR(100) NULL,
    ADD user_insurance_type         TINYINT      NULL COMMENT '1 = gesetzlich, 2 = privat',
    ADD user_health_insurance       VARCHAR(100) NULL,
    ADD user_tax_class              TINYINT UNSIGNED NULL,
    ADD user_tax_id                 VARCHAR(11)  NULL,
    ADD user_social_security_number VARCHAR(16)  NULL;


---
--- EUR --
---

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

-- Einnahme-Belege (Einnahmen, die nicht als Rechnung in InvoicePlane existieren)
CREATE TABLE IF NOT EXISTS `ip_receipts` (
  `receipt_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipt_number` VARCHAR(100) NOT NULL DEFAULT '',
  `receipt_description` VARCHAR(255) NOT NULL DEFAULT '',
  `receipt_category_id` INT(11) NULL DEFAULT NULL,
  `receipt_client_id` INT(11) NULL DEFAULT NULL,
  `receipt_date` DATE NULL DEFAULT NULL,
  `receipt_amount` DECIMAL(20,2) NOT NULL DEFAULT 0.00,
  `receipt_bank_book_date` DATE NOT NULL,
  `receipt_bank_book_subject` VARCHAR(255) NOT NULL DEFAULT '',
  `receipt_date_created` DATETIME NULL DEFAULT NULL,
  `receipt_date_modified` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`receipt_id`),
  KEY `idx_receipt_client` (`receipt_client_id`),
  KEY `idx_receipt_date` (`receipt_date`),
  KEY `idx_receipt_bank_book_date` (`receipt_bank_book_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `ip_receipts_documents` (
  `document_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receipts_id` INT(11) UNSIGNED NOT NULL,
  `document_filename` VARCHAR(255) NOT NULL,
  `document_description` VARCHAR(255) NOT NULL DEFAULT '',
  `document_created` DATETIME NOT NULL,
  `document_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`document_id`),
  KEY `idx_receipts_id` (`receipts_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bank-Buchungsdatum wird Pflichtfeld. Bestehende Zeilen ohne Datum auf das Datum der Ausgabe / des Belegs setzen.
UPDATE ip_expenses
   SET expense_bank_book_date = expense_date
 WHERE expense_bank_book_date IS NULL OR expense_bank_book_date <= '1900-01-01';

UPDATE ip_receipts
   SET receipt_bank_book_date = receipt_date
 WHERE receipt_bank_book_date IS NULL OR receipt_bank_book_date <= '1900-01-01';

-- Kontrolle: muss 0 Zeilen liefern (sonst ist auch das Datum der Ausgabe / des Belegs leer)
SELECT expense_id FROM ip_expenses WHERE expense_bank_book_date IS NULL OR expense_bank_book_date <= '1900-01-01';
SELECT receipt_id FROM ip_receipts WHERE receipt_bank_book_date IS NULL OR receipt_bank_book_date <= '1900-01-01';

-- Erst danach: Spalten auf NOT NULL festnageln
ALTER TABLE ip_expenses MODIFY expense_bank_book_date DATE NOT NULL;
ALTER TABLE ip_receipts MODIFY receipt_bank_book_date DATE NOT NULL;


-- Pflegegrad-Verlauf: jede Aenderung des Pflegegrads eines Kunden ist ein eigener Eintrag.
-- Tabellenpraefix ggf. anpassen (hier: ip_).

CREATE TABLE IF NOT EXISTS `ip_carelevel` (
  `carelevel_id`    INT(11)             NOT NULL AUTO_INCREMENT,
  `client_id`       INT(11)             NOT NULL,
  `carelevel`       TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
  `carelevel_since` DATE                NULL DEFAULT NULL,
  `timestamp`       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`carelevel_id`),
  KEY `idx_client_since` (`client_id`, `carelevel_since`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration aus ip_client_extended: je Kunde mit Pflegegrad > 0 ein Startwert.
-- Mehrfaches Ausfuehren ist ungefaehrlich (nur Kunden ohne Eintrag werden uebernommen).
-- Fehlendes/ungueltiges "seit"-Datum (NULL, 0000-00-00) bleibt NULL - es wird nichts erfunden.
INSERT INTO `ip_carelevel` (`client_id`, `carelevel`, `carelevel_since`)
SELECT e.`client_id`,
       e.`carelevel`,
       IF(e.`carelevel_since` IS NULL OR e.`carelevel_since` < '1000-01-01', NULL, e.`carelevel_since`)
FROM `ip_client_extended` e
WHERE e.`carelevel` > 0
  AND NOT EXISTS (SELECT 1 FROM `ip_carelevel` c WHERE c.`client_id` = e.`client_id`);

---
--- neue timesheets
---

-- ----------------------------------------------------------------------------
-- Daten bereinigen nur bei migration von alt
-- ----------------------------------------------------------------------------
START TRANSACTION;

UPDATE ip_timesheets SET timesheet_uuid = UUID()
 WHERE timesheet_uuid IS NULL OR timesheet_uuid = '';

UPDATE ip_timesheets SET timesheet_change_status = 'created' WHERE timesheet_change_status IS NULL;
UPDATE ip_timesheets SET timesheet_km       = 0  WHERE timesheet_km IS NULL;
UPDATE ip_timesheets SET timesheet_clientid = 0  WHERE timesheet_clientid IS NULL;
UPDATE ip_timesheets SET timesheet_remark   = '' WHERE timesheet_remark IS NULL;
UPDATE ip_timesheets SET timesheet_type     = '' WHERE timesheet_type IS NULL;

COMMIT;

-- ----------------------------------------------------------------------------
--  Schema
--
-- timesheet_minutes: Dauer in Minuten, von der DB berechnet (STORED).
--   Ende < Beginn  => ueber Mitternacht (+24 h)
--   Ende = Beginn  => 0 Minuten (z.B. Urlaub)
-- Es wird nie geschrieben, nur gelesen/summiert.
-- ----------------------------------------------------------------------------
ALTER TABLE ip_timesheets
  MODIFY timesheet_userid        INT(11)      NOT NULL,
  MODIFY timesheet_day           INT(11)      NOT NULL,
  MODIFY timesheet_month         INT(11)      NOT NULL,
  MODIFY timesheet_year          INT(11)      NOT NULL,
  MODIFY timesheet_clientid      INT(11)      NOT NULL DEFAULT 0,
  MODIFY timesheet_remark        VARCHAR(255) NOT NULL DEFAULT '',
  MODIFY timesheet_km            INT(11)      NOT NULL DEFAULT 0,
  MODIFY timesheet_type          VARCHAR(15)  NOT NULL DEFAULT '',
  MODIFY timesheet_uuid          VARCHAR(36)  NOT NULL,
  MODIFY timesheet_change_status ENUM('created','updated','deleted','synced') NOT NULL DEFAULT 'created',
  ADD COLUMN timesheet_minutes SMALLINT UNSIGNED
      GENERATED ALWAYS AS (
        (TIME_TO_SEC(timesheet_end) - TIME_TO_SEC(timesheet_start)
         + IF(timesheet_end < timesheet_start, 86400, 0)) DIV 60
      ) STORED AFTER timesheet_end,
  ADD INDEX idx_ts_user_period (timesheet_userid, timesheet_year, timesheet_month);

-- UNIQUE-Index auf die UUID: DER NEUE CODE BRAUCHT IHN (INSERT ... ON DUPLICATE KEY UPDATE).
-- Erst pruefen, ob er schon existiert (Zeile mit non_unique = 0 bedeutet: vorhanden):
SELECT index_name, non_unique
  FROM information_schema.statistics
 WHERE table_schema = DATABASE() AND table_name = 'ip_timesheets' AND column_name = 'timesheet_uuid';
-- Keine Zeile oder non_unique = 1  =>  anlegen (Schritt 1 muss vorher 0 doppelte UUIDs gezeigt haben):
-- ALTER TABLE ip_timesheets ADD UNIQUE KEY uq_ts_uuid (timesheet_uuid);

-- Kontenrahmen (SKR03) fuer Ausgaben (ip_expenses.expense_category_id) und Einnahme-Belege
-- (ip_receipts.receipt_category_id): beide Spalten enthalten ab jetzt account_id.
-- Tabellenpraefix ggf. anpassen (hier: ip_). Danach account_chart_seed.sql ausfuehren.

CREATE TABLE IF NOT EXISTS `ip_account_chart` (
  `account_id`     INT(11)      NOT NULL AUTO_INCREMENT,
  `account_type`   TINYINT(1)   NOT NULL DEFAULT 2 COMMENT '1 = Einnahme, 2 = Ausgabe',
  `account_group`  VARCHAR(64)  NOT NULL DEFAULT '' COMMENT 'Gruppe fuer die Auswahlliste (optgroup)',
  `account_number` VARCHAR(10)  NOT NULL COMMENT 'SKR03-Kontonummer als Text (fuehrende Nullen!)',
  `account_name`   VARCHAR(255) NOT NULL,
  PRIMARY KEY (`account_id`),
  KEY `idx_account_sort` (`account_type`, `account_group`, `account_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NUR falls Testbuchungen mit den alten Kategorie-Nummern (26/27/57/66/800/810/811/100) existieren:
-- die alten Werte passen nicht zu den neuen Konten und sollten geleert (oder die Testbuchungen geloescht) werden.
-- UPDATE `ip_expenses` SET `expense_category_id` = NULL;   -- schlaegt fehl, wenn die Spalte NOT NULL ist
-- UPDATE `ip_receipts` SET `receipt_category_id` = NULL;

