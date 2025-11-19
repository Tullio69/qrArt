create table languages
(
    id         int(11) unsigned auto_increment
        primary key,
    code       varchar(5)   not null,
    name       varchar(100) not null,
    flag_url   text         null,
    created_at timestamp    null,
    updated_at timestamp    null on update current_timestamp()
)
    charset = utf8mb3;

