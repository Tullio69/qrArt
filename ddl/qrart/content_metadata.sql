create table content_metadata
(
    id           int(11) unsigned auto_increment
        primary key,
    content_id   int(11) unsigned not null,
    language     varchar(5)       not null,
    content_name varchar(255)     null,
    description  text             null,
    created_at   timestamp        null,
    updated_at   timestamp        null on update current_timestamp(),
    text_only    tinyint(1)       null,
    html_content mediumtext       null,
    constraint content_metadata_ibfk_1
        foreign key (content_id) references content (id)
            on delete cascade
)
    charset = utf8mb3;

create index content_id
    on content_metadata (content_id);

