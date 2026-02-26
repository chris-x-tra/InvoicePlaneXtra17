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
alter table ip_quotes add quote_class tinyint(2) after client_id;


