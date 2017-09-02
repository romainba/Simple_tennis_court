<?php

const RES_TYPE_NONE = 0;
const RES_TYPE_NORMAL = 1;
const RES_TYPE_COURS = 2; /* cours de tennis */
const RES_TYPE_MANIF = 3; /* manifestation */
const RES_TYPE_OPENED = 4; /* reservation on-going */

const RES_TYPE = array("", "normal", "cours de tennis", "manifestation", "réservation<br>en cours");
const RES_TYPE_CLASS = array("day", "day-busy", "day-cours", "day-manif", "day-past");

const ABO_TYPE = array("Famille", "Couple", "Adulte", "Etudiant", "Junior", 
        "Cadet", "Comite", "Membre d'honneur");
    
const GRP_MANAGER = 6; /* from Joomla table */

const ERR_INVAL = 1;
const ERR_GUEST = 2;
const ERR_INTERNAL = 3;
const ERR_BUSY = 4;
const ERR_SAMEUSER = 5;
const ERR_USER1_INVAL = 6;
const ERR_USER2_INVAL = 7;
const ERR_USER1_BUSY = 8;
const ERR_USER2_BUSY = 9;
const ERR_NOT_ALLOWED = 10;
const ERR_USER1_DISABLED = 11;
const ERR_USER2_DISABLED = 12;
const ERR_TIMEOUT = 13;

const ERR_NAMES = array("",
"La requête est invalide",
"Veuillez vous identifier",
"Une erreur interne s'est produite",
"Horaire déjà occupé",
"Il n'est pas permis de mettre deux fois le m&ecirc;me joueur",
"Le premier joueur est invalide",
"Le deuxi&egrave;me joueur est invalide",
"Le premier joueur a déjà une réservation",
"Le deuxi&egrave;me joueur a déjà une réservation",
"Vous n'avez pas la permissions de réserver pour les deux joueurs",
"Le premier joueur est non actif",
"Le deuxi&egrave;me joueur est non actif",
"La requ&ecirc;te a expirée, veuillez annuler et réessayer");

?>