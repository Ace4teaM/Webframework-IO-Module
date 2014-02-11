/*==============================================================*/
/* Nom de SGBD :  PostgreSQL 8 (WFW)                            */
/* Date de création :  11/02/2014 20:59:20                      */
/*==============================================================*/


drop table if exists IO_PACKET  CASCADE;

drop table if exists IO_UPLOAD  CASCADE;

/*==============================================================*/
/* Table : IO_PACKET                                            */
/*==============================================================*/
create table IO_PACKET (
   IO_PACKET_ID         INT4                 not null,
   IO_UPLOAD_ID         VARCHAR(8)           not null,
   BASE64_DATA          TEXT                 null,
   PACKET_STATUS        BOOL                 not null,
   PACKET_NUM           INT4                 not null,
   constraint PK_IO_PACKET primary key (IO_PACKET_ID)
);

/*==============================================================*/
/* Table : IO_UPLOAD                                            */
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

alter table IO_PACKET
   add constraint FK_IO_STOCKER foreign key (IO_UPLOAD_ID)
      references IO_UPLOAD (IO_UPLOAD_ID)
      on delete restrict on update restrict;

