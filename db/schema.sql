create table cards(
    id int primary key not null auto_increment,
    token varchar(20) not null,
    customer_id bigint not null,
    b3_address text null,
    dl_address text null,
    validate_status enum('wait', 'done', 'error') default 'wait',
    update_b3_status enum('wait', 'done', 'error') default 'wait',
    validate_comment text null,
    update_comment text null,
    unique index(token, customer_id)
)