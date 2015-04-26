/*
  (C)2013,2015 AUGUEY Thomas
  PL/pgSQL
  Module IO
  
  PostgreSQL v8.3 (version minimum requise)
*/

/**
  Initialise un upload

  Parametres:
		p_file_size    : Taille du fichier en bytes
		p_filename     : Nom du fichier
		p_client_ip    : Adresse IP du client
		p_content_type : Type MIME du contenu
*/
CREATE OR REPLACE FUNCTION io_create_upload(
        p_file_size io_upload.file_size%type,
        p_filename io_upload.filename%type,
        p_client_ip io_upload.upload_client_ip%type,
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
            UPLOAD_CLIENT_IP,
            FILE_SIZE,
            PACKET_COUNT,
            CONTENT_TYPE
        )
        VALUES( 
            v_io_upload_id,
            NULL,
            v_packet_size,
            p_filename,
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

/**
  Définit une paquet

  Parametres:
		p_io_upload_id  : Identifiant de l'upload
		p_packet_num    : Numéro du paquet
		p_packet_status : ...
		p_base64_data   : Données du paquet (encodé en base64)
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
        select nextval(pg_get_serial_sequence('io_packet', 'io_packet_id')) into v_io_packet_id;
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


/**
  Supprime un upload

  Parametres:
		p_io_upload_id  : Identifiant de l'upload
*/
CREATE OR REPLACE FUNCTION io_delete_upload(
        p_io_upload_id io_upload.io_upload_id%type
)
RETURNS RESULT AS
$$
DECLARE
	v_result RESULT;
BEGIN
    delete from io_packet where io_upload_id = p_io_upload_id;
    delete from io_upload where io_upload_id = p_io_upload_id;

    /* ok */
    select 'ERR_OK', 'IO_UPLOAD_DELETED' into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;

/**
  Initialise un depot
  
  Parametres:
		p_io_repository_id  : Identifiant du dépot
		p_remote_ip         : IP du client
*/
CREATE OR REPLACE FUNCTION io_create_repository(
        p_io_repository_id io_repository.io_repository_id%type,
        p_remote_ip io_repository.remote_ip%type
)
RETURNS RESULT AS
$$
DECLARE
	v_io_repository_id varchar;
	v_result RESULT;
BEGIN

    --1. Génère le nom de dépôt
    if p_io_repository_id is null then
	select trim(to_char(100 + (random() * 800),'999D')||'-'||round(extract(epoch from now()))) into v_io_repository_id;
    else
	select trim(p_io_repository_id) into v_io_repository_id;
    end if;

    --2. Insert
    insert into io_repository (io_repository_id, remote_ip, create_date) values(v_io_repository_id, p_remote_ip, current_timestamp);

    -- Termine
    select 'ERR_OK', 'IO_REPOSITORY_CREATED', 'IO_REPOSITORY_ID:'||v_io_repository_id||';' into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;

/**
  Définit une valeur assocative d'un dépot
  
  Parametres:
		p_io_repository_id  : Identifiant du dépot
		p_name              : Nom de l'entrée
		p_value             : Valeur de l'entrée
*/
CREATE OR REPLACE FUNCTION io_set_repository_entry(
        p_io_repository_id io_repository.io_repository_id%type,
        p_name io_repository_entry.name%type,
        p_value io_repository_entry.value%type
)
RETURNS RESULT AS
$$
DECLARE
	v_io_repository_entry_id int;
	v_name varchar;
	v_value varchar;
	v_result RESULT;
BEGIN

    -- Prépare les arguments
    select trim(p_name) into v_name;
    select trim(p_value) into v_value;

    --1. Vérifie si entrée existe
    select io_repository_entry_id into v_io_repository_entry_id from io_repository_entry where name = v_name and io_repository_id = p_io_repository_id;

    --2. Insert/Actualise la valeur
    if(v_io_repository_entry_id is null) then
	select nextval(pg_get_serial_sequence('io_repository_entry', 'io_repository_entry_id')) into v_io_repository_entry_id;
	insert into io_repository_entry (io_repository_entry_id,io_repository_id,name,value) values(v_io_repository_entry_id,p_io_repository_id,v_name,v_value);
    else
	update io_repository_entry set name = v_name, value = v_value where io_repository_entry_id = v_io_repository_entry_id;
    end if;

    -- Termine
    select 'ERR_OK', 'IO_REPOSITORY_CREATED', 'IO_REPOSITORY_ENTRY_ID:'||v_io_repository_entry_id||';' into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;

/**
  Supprime un depot
  
  Parametres:
		p_io_repository_id  : Identifiant du dépot
*/
CREATE OR REPLACE FUNCTION io_delete_repository(
        p_io_repository_id io_repository.io_repository_id%type
)
RETURNS RESULT AS
$$
DECLARE
	v_result RESULT;
BEGIN

    delete from io_repository_entry where io_repository_id = p_io_repository_id;
    delete from io_repository where io_repository_id = p_io_repository_id;

    -- Termine
    select 'ERR_OK', 'IO_REPOSITORY_DELETED', 'IO_REPOSITORY_ID:'||p_io_repository_id||';' into v_result;
    return v_result;
END;
$$
LANGUAGE plpgsql;
