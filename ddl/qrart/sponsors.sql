create table sponsors
(
    id         int(11) unsigned auto_increment
        primary key,
    content_id int(11) unsigned                      not null,
    name       varchar(255)                          not null,
    link       varchar(255)                          not null,
    image_url  varchar(255)                          not null,
    created_at timestamp default current_timestamp() null,
    updated_at timestamp                             null on update current_timestamp(),
    constraint sponsors_ibfk_1
        foreign key (content_id) references content (id)
            on delete cascade
)
    charset = utf8mb3;

create index content_id
    on sponsors (content_id);

