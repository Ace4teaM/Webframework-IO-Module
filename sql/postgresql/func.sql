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
        p_client_ip io_upload.client_ip%type,
        p_content_type io_upload.content_type%type
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
            PACKET_COUNT,
            CONTENT_TYPE
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
            v_packet_count,
            p_content_type
        );

    /* ok */
    select 'ERR_OK', 'IO_UPLOAD_CREATED', 'IO_UPLOAD_ID:'||v_io_upload_id||';PACKET_COUNT:'||v_packet_count||';PACKET_SIZE:'||v_packet_size||';' into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;

/*
  Définit une paquet
*/
CREATE OR REPLACE FUNCTION io_set_packet(
        p_io_upload_id io_upload.io_upload_id%type,
        p_packet_num io_packet.packet_num%type,
        p_packet_status io_packet.packet_status%type,
        p_base64_data io_packet.base64_data%type
)
RETURNS RESULT AS
$$
DECLARE
	v_io_packet_id io_packet.io_packet_id%type;
	v_result RESULT;
BEGIN
    select io_packet_id into v_io_packet_id from io_packet where io_upload_id = p_io_upload_id and packet_num = p_packet_num;
    if v_io_packet_id is not null then 
        update io_packet set packet_status = p_packet_status and base64_data = p_base64_data
            where io_packet_id = v_io_packet_id;
    else
        select coalesce(max(io_packet_id)+1,1) into v_io_packet_id from io_packet;
        insert into io_packet
            VALUES(v_io_packet_id,p_io_upload_id,p_base64_data,p_packet_status,p_packet_num);
    end if;

    -- ok
    select 'ERR_OK', 'IO_PACKET_SET', 'PACKET_NUM:'||p_packet_num||';IO_PACKET_ID:'||v_io_packet_id||';' into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;

/*
  Définit une paquet

CREATE OR REPLACE FUNCTION io_get_data(
        p_io_upload_id io_upload.io_upload_id%type
)
RETURNS RESULT AS
$$
DECLARE
        v_rec io_packet%rowtype;
	tmp VARCHAR;
	v_base64_data TEXT default NULL;
	v_result RESULT;
BEGIN
    --select base64_data from io_packet where io_upload_id = p_io_upload_id order by packet_num;
    FOR v_rec IN select * from io_packet where io_upload_id = p_io_upload_id order by packet_num LOOP
	if v_base64_data is null then
		v_base64_data='';
	end if;
        v_base64_data = v_base64_data||decode(v_rec.base64_data,'base64');
    END LOOP;

    if v_base64_data is null then
	select 'ERR_FAILED', 'IO_NO_DATA_FOUND'  into v_result;
	return v_result;
    end if;

    -- pas de données 
    select 'ERR_OK', 'SUCCESS', 'BASE64_DATA:'||encode(v_base64_data,'base64')||';'  into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;*/
