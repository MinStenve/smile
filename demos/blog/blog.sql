create table user(
    id INTEGER not null PRIMARY KEY AUTOINCREMENT,
    email char(255) not null,
    password char(32) not null
);
insert into user (email, password) values ('admin@admin.com', '123456');
create table archive(
    id INTEGER not null PRIMARY KEY AUTOINCREMENT,
    author int(11) not null,
    title char(255) not null,
    content char(255) not null
);

