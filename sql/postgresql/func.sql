/*
  (C)2013 AUGUEY Thomas
  PL/pgSQL
  Module Mailing (WFW_MAILING)
  
  PostgreSQL v8.3 (version minimum requise)
*/

/*
  Initialise un upload
*/
CREATE OR REPLACE FUNCTION io_create_upload(
        p_file_size io_upload.file_size%type,
        p_filename io_upload.filename%type,
        p_output_path io_upload.output_path%type,
        p_upload_path io_upload.upload_path%type, -- si NULL, l'upload est réalisé en base
        p_client_ip io_upload.client_ip%type
)
RETURNS RESULT AS
$$
DECLARE
	v_io_upload_id varchar;
	v_packet_size int := 524288;
        v_packet_count int;
	v_result RESULT;
BEGIN
    /* calcule le nombre de paquets necessaires */
    v_packet_count = ceil(float8(p_file_size) / v_packet_size);

    /*
        token d'identification
        prefix l'identicateur du caractere 'u' pour etre un identificateur valide
    */
    loop
        select 'u'||random_token from random_token(7) into v_io_upload_id;
        if (select count(*) from io_upload where io_upload_id=v_io_upload_id) < 1 then
            exit;
        end if;
    end loop;

    /* io_upload */
    insert into io_upload
        (
            IO_UPLOAD_ID,
            CHECKSUM,
            PACKET_SIZE,
            FILENAME,
            OUTPUT_PATH,
            UPLOAD_PATH,
            CLIENT_IP,
            FILE_SIZE,
            PACKET_COUNT
        )
        VALUES( 
            v_io_upload_id,
            NULL,
            v_packet_size,
            p_filename,
            p_output_path,
            p_upload_path,
            p_client_ip,
            p_file_size,
            v_packet_count
        );

    /* ok */
    select 'ERR_OK', 'IO_UPLOAD_CREATED', 'IO_UPLOAD_ID:'||v_io_upload_id||';PACKET_COUNT:'||v_packet_count||';PACKET_SIZE:'||v_packet_size||';' into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;
