/*
  (C)2014 Thomas AUGUEY
  PL/pgSQL
  Module IO
  
  Index, Sequences et autres objets
*/

/*
    Sequences
    Liste des sequences d'auto incrementation pour les identifiants 
*/
DROP SEQUENCE IF EXISTS io_packet_seq;
CREATE SEQUENCE io_packet_seq START 1;