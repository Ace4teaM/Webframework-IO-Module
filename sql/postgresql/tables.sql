/*==============================================================*/
/* DBMS name:      PostgreSQL 8 (WFW)                           */
/* Created on:     31/08/2014 21:33:14                          */
/*==============================================================*/


drop index  if exists R1_FK;

drop index  if exists IO_PACKET_PK;

drop table if exists IO_PACKET  CASCADE;

drop index  if exists IO_REPOSITORY_PK;

drop table if exists IO_REPOSITORY  CASCADE;

drop index  if exists R2_FK;

drop index  if exists IO_REPOSITORY_ENTRY_PK;

drop table if exists IO_REPOSITORY_ENTRY  CASCADE;

drop index  if exists IO_UPLOAD_PK;

drop table if exists IO_UPLOAD  CASCADE;

/*==============================================================*/
/* Table: IO_PACKET                                             */
/*==============================================================*/
create table IO_PACKET (
   IO_PACKET_ID         SERIAL               not null,
   IO_UPLOAD_ID         VARCHAR(8)           not null,
   BASE64_DATA          TEXT                 null,
   PACKET_STATUS        BOOL                 not null,
   PACKET_NUM           INT4                 not null,
   constraint PK_IO_PACKET primary key (IO_PACKET_ID)
);

/*==============================================================*/
/* Index: IO_PACKET_PK                                          */
/*==============================================================*/
create unique index IO_PACKET_PK on IO_PACKET (
IO_PACKET_ID
);

/*==============================================================*/
/* Index: R1_FK                                                 */
/*==============================================================*/
create  index R1_FK on IO_PACKET (
IO_UPLOAD_ID
);

/*==============================================================*/
/* Table: IO_REPOSITORY                                         */
/*==============================================================*/
create table IO_REPOSITORY (
   IO_REPOSITORY_ID     VARCHAR(256)         not null,
   REMOTE_IP            VARCHAR(15)          not null,
   CREATE_DATE          TIMESTAMP            not null,
   constraint PK_IO_REPOSITORY primary key (IO_REPOSITORY_ID)
);

comment on table IO_REPOSITORY is
'Stockage associatif';

comment on column IO_REPOSITORY.IO_REPOSITORY_ID is
'Identifiant du depot';

comment on column IO_REPOSITORY.REMOTE_IP is
'Adresse IP de l''utilisateur qui a créé le depot';

comment on column IO_REPOSITORY.CREATE_DATE is
'Date de creation du depot';

/*==============================================================*/
/* Index: IO_REPOSITORY_PK                                      */
/*==============================================================*/
create unique index IO_REPOSITORY_PK on IO_REPOSITORY (
IO_REPOSITORY_ID
);

/*==============================================================*/
/* Table: IO_REPOSITORY_ENTRY                                   */
/*==============================================================*/
create table IO_REPOSITORY_ENTRY (
   IO_REPOSITORY_ENTRY_ID SERIAL               not null,
   IO_REPOSITORY_ID     VARCHAR(256)         not null,
   NAME                 VARCHAR(120)         not null,
   VALUE                TEXT                 null,
   constraint PK_IO_REPOSITORY_ENTRY primary key (IO_REPOSITORY_ENTRY_ID),
   constraint AK_NAME_IO_REPOS unique (NAME, IO_REPOSITORY_ENTRY_ID)
);

comment on column IO_REPOSITORY_ENTRY.IO_REPOSITORY_ID is
'Identifiant du depot';

/*==============================================================*/
/* Index: IO_REPOSITORY_ENTRY_PK                                */
/*==============================================================*/
create unique index IO_REPOSITORY_ENTRY_PK on IO_REPOSITORY_ENTRY (
IO_REPOSITORY_ENTRY_ID
);

/*==============================================================*/
/* Index: R2_FK                                                 */
/*==============================================================*/
create  index R2_FK on IO_REPOSITORY_ENTRY (
IO_REPOSITORY_ID
);

/*==============================================================*/
/* Table: IO_UPLOAD                                             */
/*==============================================================*/
create table IO_UPLOAD (
   IO_UPLOAD_ID         VARCHAR(8)           not null,
   CHECKSUM             VARCHAR(256)         null,
   PACKET_SIZE          INT4                 not null,
   FILENAME             VARCHAR(260)         not null,
   UPLOAD_CLIENT_IP     VARCHAR(16)          not null,
   BEGIN_DATE           TIMESTAMP            not null,
   FILE_SIZE            INT4                 not null,
   UPLOAD_COMPLETE      BOOL                 not null,
   PACKET_COUNT         INT4                 not null,
   CONTENT_TYPE         VARCHAR(260)         not null,
   constraint PK_IO_UPLOAD primary key (IO_UPLOAD_ID)
);

/*==============================================================*/
/* Index: IO_UPLOAD_PK                                          */
/*==============================================================*/
create unique index IO_UPLOAD_PK on IO_UPLOAD (
IO_UPLOAD_ID
);

alter table IO_PACKET
   add constraint FK_R1 foreign key (IO_UPLOAD_ID)
      references IO_UPLOAD (IO_UPLOAD_ID)
      on delete restrict on update restrict;

alter table IO_REPOSITORY_ENTRY
   add constraint FK_R2 foreign key (IO_REPOSITORY_ID)
      references IO_REPOSITORY (IO_REPOSITORY_ID)
      on delete restrict on update restrict;

