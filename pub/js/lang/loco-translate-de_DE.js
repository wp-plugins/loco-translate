/**
 * Loco js export: JavaScript function
 * Project: loco.js conversion
 * Release: Working copy
 * Locale: de_DE, German
 * Exported at: Thu, 02 Jan 2014 22:24:49 +0000 
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
    {"Error":"Fehler","Warning":"Warnung","OK":"OK","Settings saved":"Einstellungen gespeichert","New PO file":"Neue PO-Datei","You must specify a valid locale for a new PO file":"Du musst einen g\u00fcltigen Pfad f\u00fcr die PO-Datei angeben","No translatable strings found":"Keine \u00fcbersetzbaren Strings gefunden","Cannot create a PO file.":"Erstellen der PO-Datei fehlgeschlagen.","PO file already exists with locale %s":"PO-Datei existiert schon unter %s","File cannot be created automatically. Fix the file permissions or use Download instead of Save":"Datei kann nicht automatisch erstellt werden. Setze die Datei-Zugriffsrechte oder benutze Download anstatt Speichern","%s file is empty":"Datei (%s) ist leer","Run Sync to update from source code":"Benutze Sync, um von der Quelle zu aktualisieren","No strings could be extracted from source code":"","Run Sync to update from %s":"","Source code has been modified, run Sync to update POT":"","POT has been modified since PO file was saved, run Sync to update":"","Bad file path":"Falscher Dateipfad","New template":"Neues Template","New language":"Neue Sprache","%s%% translated":"%s%% \u00fcbersetzt","1 string":{"one":""},"%s fuzzy":"","%s untranslated":"%s nicht \u00fcbersetzt","Loco, Translation Management":"Loco, Translation Management","Translation":"\u00dcbersetzung","Manage translations":"\u00dcbersetzungen verwalten","Settings":"Einstellungen","Unknown language":"Unbekannte Sprache","Some files not writable":"Einige Dateien nicht schreibbar","Some files missing":"Einige Dateien fehlen","\"%s\" folder not writable":"Ordner \"%s\" nicht schreibbar","Folder not writable":"Ordner nicht schreibbar","POT file not writable":"POT-Datei nicht schreibbar","PO file not writable":"PO-Datei nicht schreibbar","MO file not writable":"MO-Datei nicht schreibbar","MO file not found":"MO-Datei nicht gefunden","User does not have permission to manage translations":"Benutzer hat keine Rechte die \u00dcbersetzungen zu verwalten","Invalid data posted to server":"Ung\u00fcltige Daten zum Server gesendet","Package not found called %s":"Package %s nicht gefunden","Web server cannot create \"%s\" directory in \"%s\". Fix file permissions or create it manually.":"Webserver kann Ordner \"%s\" nicht in \"%s\" erstellen. Setze die Zugriffsrechte oder erstelle den Ordner selbst.","Web server cannot create files in the \"%s\" directory. Fix file permissions or use the download function.":"","%s file is not writable by the web server. Fix file permissions or download and copy to \"%s\/%s\".":"","Cannot create MO file":"","Cannot overwrite MO file":"","Failed to compile MO file with %s, check your settings":"","Failed to compile MO file with built-in compiler":"","No strings could be extracted from source files":"","Unknown error":"","PO file saved":"","and MO file compiled":"","Merged from %s":"","Merged from source code":"","Already up to date with %s":"","Already up to date with source code":"","1 new string added":{"one":""},"1 obsolete string removed":{"one":""},"Your changes will be lost if you continue without saving":"","Packages":"","File check":"","File system permissions for %s":"Dateisystem-Rechte f\u00fcr %s","Back":"Zur\u00fcck","Package details":"","Translations (PO)":"\u00dcbersetzungen (PO)","Template (POT)":"","File permissions":"Dateizugriffsrechte","1 language":{"one":"1 Sprache","other":"%u Sprachen"},"Updated":"Aktualisiert","Powered by":"","Configure Loco Translate":"","Compiling MO files":"","Use built-in MO compiler.":"","Use external command:":"","Enter path to msgfmt on server":"","Save settings":"","Get help":"","Template file":"","Switch to...":"","never":"","Save":"","Download":"","Sync":"","Revert":"","Add":"","Del":"","Fuzzy":"","Filter translations":"","Help":"","Initialize new translations in %s":"","Select from common languages":"","or enter any language code":"","Start translating":"","New version available":"","Upgrade to version %s of Loco Translate":"","Select a plugin or theme to translate":"W\u00e4hle ein Plugin oder Theme zum \u00dcbersetzen","Themes":"","Plugins":"","Core":""} 
);
