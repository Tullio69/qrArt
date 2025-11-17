create table content_files
(
    id          int(11) unsigned auto_increment
        primary key,
    content_id  int(11) unsigned                      not null,
    metadata_id int(11) unsigned                      null,
    file_type   varchar(50)                           not null,
    file_url    text                                  not null,
    created_at  timestamp default current_timestamp() null,
    updated_at  timestamp default current_timestamp() null on update current_timestamp(),
    constraint content_files_ibfk_1
        foreign key (content_id) references content (id)
            on delete cascade,
    constraint content_files_ibfk_2
        foreign key (metadata_id) references content_metadata (id)
            on delete cascade
)
    charset = utf8mb3;

create index content_id
    on content_files (content_id);

create index metadata_id
    on content_files (metadata_id);

