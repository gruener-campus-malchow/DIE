# API Dokumentation (Draft)

Die API hat durch die Regeln in der .htaccess-Datei die Möglichkeit, REST-konforme URLs zu verarbeiten.

Typische GET-Requests aus herkömmlichen Formularen werden nicht unterstützt.

Die Architektur folgt grundsätzlich den Ideen des MVC-Konzepts, überlässt den View-Anteil aber der anfragenden Instanz.

Der Controller-Teil befindet sich in der index.php, die Modelle im models-Verzeichnis.

## index.php

### Aufgaben

* Konfiguration lesen
* Datenbank bereit stellen
* URL auswerten
* GET und POST unterscheiden
* Modell bestimmen
* Modell instanziieren (TODO!!! Request-Methode mitteilen!!!)
* Daten des Modells in JSON umwandeln
* JSON zurück geben
* Testfunktionen bereitstellen

### Überlegungen

* Mailing

## config.php

* Datenbank
* Debug-Mode
* E-Mail
