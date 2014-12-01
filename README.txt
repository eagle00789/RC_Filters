/**
 * Filters
 *
 * Plugin that adds a new tab to the settings section to create client-side e-mail filtering.
 *
 * @version 2.1.5
 * @author Roberto Zarrelli <zarrelli@unimol.it>
 * @author Chris Simon <info@decomputeur.nl> from version 2.1.3
 *  
 */

Setup
-----
To install the plugin you have to: 
1. copy the filters folder in the plugins folder of roundcube;
2. add "filters" in the plugins section of the roundcube configuration (config/main.inc.php). 

To setup the plugin, make a copy of the file config.inc.php.dist and save it as config.inc.php and make changes according to the directions in the configfile.
If you do not make the required copy, or you do not make any changes in the copy, the script will automaticly create a spamfilter rule with the subject ***SPAM***, decode base64 messages and does a case insensitive search when a filter is being executed.


History
-------

1.0 Initial version.
1.1 Fixed some important issues.
1.2 Fixed some minor issues - thanks to Marco De Vivo. 
1.3 Fixed some minor issues and added additional translations: Dutch and French - thanks to Ruud van den Hout.
1.4 News: each rule can now filter all, read or unread messages.
1.5 Fixed some important issues detected with Roundcube 0.8
1.6 Added additional translation: German - thanks to Fynn Kardel. 
1.7 Added additional translation: Russian - thanks to AresMax. 
1.8 Added additional translation: Czech - thanks to Miroslav Baka.
1.9 Added additional translation: Spanish - thanks to Yoni (MyRoundcube Dev Team - www.myroundcube.com). 
1.9.1 Added additional translations: Polish - thanks to Damian Wrzalski; Slovak - thanks to Miki.
1.9.2: 
  - Added additional translation: Portugal - thanks to antoniomr. 
  - Fixed the UTF-8 coding on the German translation - thanks to Veit.
  - Added the contrib section with third-party scripts.
  - Thanks to Carsten Schumann to write the manual filter patch for Filters 1.9.2 which adds the option to filter manually on request (i.e. to move all newsletters/alerts from inbox to trash).
    The patch expands the settings page with an option "Mode: automatic/manual" and adds a "manual filter" button to the toolbar. Finally, it updates the localization files.
2.0:
  - Added the 'auto add spam filter rule' which automatically add the rule to move messages into junk folder.
  - Added additional translations: Taiwan - thanks to Avery Wu;
  - Added additional translations: Romanian - thanks to Tache Madalin;
  - Fixed to UTF-8 the French translation - thanks to Nvirenque.
2.1:
  - Added the feature to filter base64 encoded mail messages;
  - Added the feature to filter messages searching in case insensitive or case sensitive mode;
  - Improved the code to prevent the javascript injection - thanks to Moritz;
  - Improved code organization;
  - Minor bug fixes.
2.1.1:
  - Fixed a bug which prevented to insert case sensitive search strings - thanks to Emanuele Bruno.
2.1.2:
  - Added a dynamic vertical scrollbar when there are a lot of filters to show - thanks to Alain Martini.      
2.1.3:
  - Added a notification when the plugin caused 1 or more messages to be moved,
2.1.4:
  - Make use of some internal structures from roundcube for a config file to keep the plugin compatible with new versions of roundcube
2.1.5:
  - Added the first hints of a per filter case sensitivity switch
2.1.6:
  - Finished the work on the case sensitive per filter option