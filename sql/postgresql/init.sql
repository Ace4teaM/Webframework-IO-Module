/*
  (C)2013 Thomas AUGUEY
  PL/pgSQL
  Module IO
  
  Initialise les objets et le contenu de base avant utilisation
*/


/*
--------------------------------------------------------------------------
     Defauts
--------------------------------------------------------------------------
*/

-- définit la date en cours aux avis
ALTER TABLE io_upload ALTER COLUMN begin_date SET DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE io_upload ALTER COLUMN upload_complete SET DEFAULT FALSE;
