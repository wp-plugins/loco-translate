/**
 * Loco js export: JavaScript function
 * Project: loco.js conversion
 * Release: Working copy
 * Locale: de_DE, German
 * Exported at: Fri, 03 Jan 2014 12:23:29 +0000 
 */
loco = window.loco||{}, loco.t = function( pairs ){
    
    // named plural forms according to Unicode 
    var pluralForms = ["one","other"];
    
    // calc numeric index of a plural form (0-1)
    function pluralIndex( n ){
        return Number( (n != 1) );
    }

    // expose public t() function
    return function( msgid1, msgid2, n ){
        var value = pairs[msgid1];
        // singular if no multiplier
        if( null == n ){
            n = 1;
        }
        // plurals stored as objects, e.g. { one: "" }
        if( value instanceof Object ){
            value = value[ pluralForms[ pluralIndex(n) ] || 'one' ];
        }
        return value || ( 1 === n ? msgid1 : msgid2 ) || msgid1 || '';
    };
}(
    {"%s untranslated":"%s nicht \u00fcbersetzt","File download failed":"","Failed to compile MO file with built-in compiler":"","Unknown error":"","PO file saved":"","and MO file compiled":"","Merged from %s":"","Merged from source code":"","Already up to date with %s":"","Already up to date with source code":"","1 new string added":{"one":""},"1 obsolete string removed":{"one":""},"Your changes will be lost if you continue without saving":"","Compiling MO files":"","Use built-in MO compiler.":"","Use external command:":"","Enter path to msgfmt on server":"Pfad zum msgfmt-Programm","Get help":"","Error":"Fehler","Warning":"Warnung","OK":"OK","Settings saved":"Einstellungen gespeichert","New PO file":"Neue PO-Datei","You must specify a valid locale for a new PO file":"Du musst einen g\u00fcltigen Pfad f\u00fcr die PO-Datei angeben","No translatable strings found":"Keine \u00fcbersetzbaren Zeichenketten gefunden","Cannot create a PO file.":"Erstellen der PO-Datei fehlgeschlagen.","PO file already exists with locale %s":"PO-Datei existiert schon unter %s","File cannot be created automatically. Fix the file permissions or use Download instead of Save":"Datei kann nicht automatisch erstellt werden. Setze die Datei-Zugriffsrechte oder benutze Download anstatt Speichern","%s file is empty":"Datei (%s) ist leer","Run Sync to update from source code":"Benutze Sync, um von der Quelle zu aktualisieren","No strings could be extracted from source code":"Es konnten keine Zeichenketten vom Quellcode extrahiert werden","Run Sync to update from %s":"Benutze Sync, um aus %s zu aktualisieren","Source code has been modified, run Sync to update POT":"Quellcode wurde ge\u00e4ndert, benutze Sync um POT zu aktualisieren","POT has been modified since PO file was saved, run Sync to update":"POT wurde aktualisiert bevor die PO-Datei gespeichert wurde. Benutze Sync zum Aktualisieren.","Bad file path":"Falscher Dateipfad","New template":"Neues Template","New language":"Neue Sprache","%s%% translated":"%s%% \u00fcbersetzt","1 string":{"one":"1 Zeichenkette","other":"%s Zeichenketten"},"%s fuzzy":"%s undeutlich","Loco, Translation Management":"Loco, Translation Management","Translation":"\u00dcbersetzung","Manage translations":"\u00dcbersetzungen verwalten","Settings":"Einstellungen","Unknown language":"Unbekannte Sprache","Some files not writable":"Einige Dateien nicht schreibbar","Some files missing":"Einige Dateien fehlen","\"%s\" folder not writable":"Ordner \"%s\" nicht schreibbar","Folder not writable":"Ordner nicht schreibbar","POT file not writable":"POT-Datei nicht schreibbar","PO file not writable":"PO-Datei nicht schreibbar","MO file not writable":"MO-Datei nicht schreibbar","MO file not found":"MO-Datei nicht gefunden","User does not have permission to manage translations":"Benutzer hat keine Rechte die \u00dcbersetzungen zu verwalten","Failed to compile MO file with %s, check your settings":"Kompilieren der MO-Datei fehlgeschlagen. Bitte pr\u00fcfe Deine Einstellungen","Invalid data posted to server":"Ung\u00fcltige Daten zum Server gesendet","Package not found called %s":"Paket %s nicht gefunden","Web server cannot create \"%s\" directory in \"%s\". Fix file permissions or create it manually.":"Webserver kann Ordner \"%s\" nicht in \"%s\" erstellen. Setze die Zugriffsrechte oder erstelle den Ordner selbst.","Web server cannot create files in the \"%s\" directory. Fix file permissions or use the download function.":"Webserver kann keine Dateien im Ordner \"%s\" erstellen. Setze die Datei-Zugriffsrechte oder benutze Download anstatt Speichern.","%s file is not writable by the web server. Fix file permissions or download and copy to \"%s\/%s\".":"Datei %s ist vom Webserver nicht schreibbar. Setze die Datei-Zugriffsrechte oder benutze Download anstatt Speichern und speichere die Datei auf dem Server unter \"%s\/%s\" ab.","Cannot create MO file":"Erstellen der MO-Datei fehlgeschlagen.","Cannot overwrite MO file":"\u00dcberschreiben der MO-Datei fehlgeschlagen","No strings could be extracted from source files":"Es konnten keine Zeichenketten aus der Quelle extrahiert werden","Packages":"Pakete","File check":"Dateipr\u00fcfung","File system permissions for %s":"Dateisystem-Rechte f\u00fcr %s","Back":"Zur\u00fcck","Package details":"Paketdetails","Translations (PO)":"\u00dcbersetzungen (PO)","Template (POT)":"Template (POT)","File permissions":"Dateizugriffsrechte","1 language":{"one":"1 Sprache","other":"%u Sprachen"},"Updated":"Aktualisiert","Powered by":"Pr\u00e4sentiert von","Configure Loco Translate":"Konfiguriere Loco Translate","Save settings":"Einstellungen speichern","Template file":"Templatedatei","Switch to...":"Wechsle zu...","never":"niemals","Save":"Speichern","Download":"Download","Sync":"Sync","Revert":"zur\u00fccksetzen","Add":"Hinzuf\u00fcgen","Del":"L\u00f6schen","Fuzzy":"Undeutlich","Filter translations":"\u00dcbersetzungen filtern","Help":"Hilfe","Initialize new translations in %s":"Initialisiere neue \u00dcbersetzung in %s","Select from common languages":"W\u00e4hle aus den Standardsprachen","or enter any language code":"oder trage den Sprach-Code ein","Start translating":"Starte \u00dcbersetzung","New version available":"Neue Version verf\u00fcgbar","Upgrade to version %s of Loco Translate":"Bitte upgrade Loco Translate auf Version %s","Select a plugin or theme to translate":"W\u00e4hle ein Plugin oder Theme zum \u00dcbersetzen","Themes":"Themes","Plugins":"Plugins","Core":"Core"} 
);
