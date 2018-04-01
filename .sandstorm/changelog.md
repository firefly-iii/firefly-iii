# 4.7.2
- [Issue 1123](https://github.com/firefly-iii/firefly-iii/issues/1123) First browser based update routine.
- Add support for Italian.
- [Issue 1232](https://github.com/firefly-iii/firefly-iii/issues/1232) Allow user to specify Docker database port.
- [Issue 1197](https://github.com/firefly-iii/firefly-iii/issues/1197) Beter account list overview 
- [Issue 1202](https://github.com/firefly-iii/firefly-iii/issues/1202) Some budgetary warnings 
- [Issue 1284](https://github.com/firefly-iii/firefly-iii/issues/1284) Experimental support for bunq import
- [Issue 1248](https://github.com/firefly-iii/firefly-iii/issues/1248) Ability to import BIC, ability to import SEPA fields. 
- [Issue 1102](https://github.com/firefly-iii/firefly-iii/issues/1102) Summary line for bills 
- More info to debug page.
- [Issue 1186](https://github.com/firefly-iii/firefly-iii/issues/1186) You can see the latest account balance in CRUD forms 
- Add Kubernetes YAML files, kindly created by a FF3 user.
- [Issue 1244](https://github.com/firefly-iii/firefly-iii/issues/1244) Better line for "today" marker and add it to other chart as well ([issue 1214](https://github.com/firefly-iii/firefly-iii/issues/1214))
- [Issue 1219](https://github.com/firefly-iii/firefly-iii/issues/1219) Languages in dropdown 
- [Issue 1189](https://github.com/firefly-iii/firefly-iii/issues/1189) Inactive accounts get removed from net worth 
- [Issue 1220](https://github.com/firefly-iii/firefly-iii/issues/1220) Attachment description and notes migrated to just "notes". 
- [Issue 1236](https://github.com/firefly-iii/firefly-iii/issues/1236) Multi currency balance box 
- [Issue 1240](https://github.com/firefly-iii/firefly-iii/issues/1240) Better overview for accounts. 
- [Issue 1292](https://github.com/firefly-iii/firefly-iii/issues/1292) Removed some charts from the "all"-overview of budgets and categories 
- [Issue 1245](https://github.com/firefly-iii/firefly-iii/issues/1245) Improved recognition of IBANs 
- Improved import routine.
- Update notifier will wait three days before notifying users.
- [Issue 1300](https://github.com/firefly-iii/firefly-iii/issues/1300) Virtual balance of credit cards does not count for net worth 
- [Issue 1247](https://github.com/firefly-iii/firefly-iii/issues/1247) Can now see overspent amount 
- [Issue 1221](https://github.com/firefly-iii/firefly-iii/issues/1221) Upgrade to Laravel 5.6 
- [Issue 1187](https://github.com/firefly-iii/firefly-iii/issues/1187) Updated the password verifier to use Troy Hunt's new API 
- Revenue chart is now on frontpage permanently
- [Issue 1153](https://github.com/firefly-iii/firefly-iii/issues/1153) 2FA settings are in your profile now 
- [Issue 1227](https://github.com/firefly-iii/firefly-iii/issues/1227) Can set the timezone in config or in Docker 
- [Issue 1294](https://github.com/firefly-iii/firefly-iii/issues/1294) Ability to link a transaction to itself 
- Correct reference to journal description in split form.
- [Issue 1234](https://github.com/firefly-iii/firefly-iii/issues/1234) Fix budget page issues in SQLite 
- [Issue 1262](https://github.com/firefly-iii/firefly-iii/issues/1262) Can now use double and epty headers in CSV files 
- [Issue 1258](https://github.com/firefly-iii/firefly-iii/issues/1258) Fixed a possible date mismatch in piggy banks
- [Issue 1283](https://github.com/firefly-iii/firefly-iii/issues/1283) Bulk delete was broken 
- [Issue 1293](https://github.com/firefly-iii/firefly-iii/issues/1293) Layout problem with notes 
- [Issue 1257](https://github.com/firefly-iii/firefly-iii/issues/1257) Improve transaction lists query count 
- [Issue 1291](https://github.com/firefly-iii/firefly-iii/issues/1291) Fixer IO problems 
- [Issue 1239](https://github.com/firefly-iii/firefly-iii/issues/1239) Could not edit expense or revenue accounts ([issue 1298](https://github.com/firefly-iii/firefly-iii/issues/1298)) 
- [Issue 1297](https://github.com/firefly-iii/firefly-iii/issues/1297) Could not convert to withdrawal 
- [Issue 1226](https://github.com/firefly-iii/firefly-iii/issues/1226) Category overview in default report shows no income. 
- Various other bugs and problems ([issue 1198](https://github.com/firefly-iii/firefly-iii/issues/1198), [issue 1213](https://github.com/firefly-iii/firefly-iii/issues/1213), [issue 1237](https://github.com/firefly-iii/firefly-iii/issues/1237), [issue 1238](https://github.com/firefly-iii/firefly-iii/issues/1238), [issue 1199](https://github.com/firefly-iii/firefly-iii/issues/1199), [issue 1200](https://github.com/firefly-iii/firefly-iii/issues/1200))

### Security
- Fixed an issue with token validation on the command line.

# 4.7.1
- A brand new API. Read about it in the [documentation](http://firefly-iii.readthedocs.io/en/latest/).
- Add support for Spanish. [issue 1194](https://github.com/firefly-iii/firefly-iii/issues/1194)
- Some custom preferences are selected by default for a better user experience.
- Some new currencies [issue 1211](https://github.com/firefly-iii/firefly-iii/issues/1211)
- Fixed [issue 1155](https://github.com/firefly-iii/firefly-iii/issues/1155) (reported by [ndandanov](https://github.com/ndandanov))
- [Issue 1156](https://github.com/firefly-iii/firefly-iii/issues/1156) [issue 1182](https://github.com/firefly-iii/firefly-iii/issues/1182) and other issues related to SQLite databases.
- Multi-page budget overview was broken (reported by [jinformatique](https://github.com/jinformatique))
- Importing CSV files with semi-colons in them did not work [issue 1172](https://github.com/firefly-iii/firefly-iii/issues/1172) [issue 1183](https://github.com/firefly-iii/firefly-iii/issues/1183) [issue 1210](https://github.com/firefly-iii/firefly-iii/issues/1210)
- Could not use account number that was in use by a deleted account [issue 1174](https://github.com/firefly-iii/firefly-iii/issues/1174) 
- Fixed spelling error that lead to 404's [issue 1175](https://github.com/firefly-iii/firefly-iii/issues/1175) [issue 1190](https://github.com/firefly-iii/firefly-iii/issues/1190)
- Fixed tag autocomplete [issue 1178](https://github.com/firefly-iii/firefly-iii/issues/1178)
- Better links for "new transaction" buttons [issue 1185](https://github.com/firefly-iii/firefly-iii/issues/1185)
- Cache errors in budget charts [issue 1192](https://github.com/firefly-iii/firefly-iii/issues/1192)
- Deleting transactions that are linked to other other transactions would lead to errors [issue 1209](https://github.com/firefly-iii/firefly-iii/issues/1209)

# 4.7.0
- Support for Russian and Portuguese (Brazil)
- Support for the Spectre API (Salt Edge)
- Many strings now translatable thanks to [Nik-vr](https://github.com/Nik-vr) ([issue 1118](https://github.com/firefly-iii/firefly-iii/issues/1118), [issue 1116](https://github.com/firefly-iii/firefly-iii/issues/1116), [issue 1109](https://github.com/firefly-iii/firefly-iii/issues/1109), )
- Many buttons to quickly create stuff
- Sum of tables in reports, requested by [MacPaille](https://github.com/MacPaille) ([issue 1106](https://github.com/firefly-iii/firefly-iii/issues/1106))
- Future versions of Firefly III will notify you there is a new version, as suggested by [8bitgentleman](https://github.com/8bitgentleman) in [issue 1050](https://github.com/firefly-iii/firefly-iii/issues/1050)
- Improved net worth box [issue 1101](https://github.com/firefly-iii/firefly-iii/issues/1101) ([Nik-vr](https://github.com/Nik-vr))
- Nice dropdown in transaction list [issue 1082](https://github.com/firefly-iii/firefly-iii/issues/1082)
- Better support for local fonts thanks to [devlearner](https://github.com/devlearner) ([issue 1145](https://github.com/firefly-iii/firefly-iii/issues/1145))
- Improve attachment support and view capabilities (suggested by [trinhit](https://github.com/trinhit) in [issue 1146](https://github.com/firefly-iii/firefly-iii/issues/1146))
- Whole new [read me file](https://github.com/firefly-iii/firefly-iii/blob/master/readme.md), [new end user documentation](https://firefly-iii.readthedocs.io/en/latest/) and an [updated website](https://www.firefly-iii.org/)!
- Many charts and info-blocks now scale property ([issue 989](https://github.com/firefly-iii/firefly-iii/issues/989) and [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040))
- Charts work in IE thanks to [devlearner](https://github.com/devlearner) ([issue 1107](https://github.com/firefly-iii/firefly-iii/issues/1107))
- Various fixes in import routine
- Bug that left charts empty ([issue 1088](https://github.com/firefly-iii/firefly-iii/issues/1088)), reported by various users amongst which [jinformatique](https://github.com/jinformatique)
- [Issue 1124](https://github.com/firefly-iii/firefly-iii/issues/1124), as reported by [gavu](https://github.com/gavu)
- [Issue 1125](https://github.com/firefly-iii/firefly-iii/issues/1125), as reported by [gavu](https://github.com/gavu)
- [Issue 1126](https://github.com/firefly-iii/firefly-iii/issues/1126), as reported by [gavu](https://github.com/gavu)
- [Issue 1131](https://github.com/firefly-iii/firefly-iii/issues/1131), as reported by [dp87](https://github.com/dp87)
- [Issue 1129](https://github.com/firefly-iii/firefly-iii/issues/1129), as reported by [gavu](https://github.com/gavu)
- [Issue 1132](https://github.com/firefly-iii/firefly-iii/issues/1132), as reported by [gavu](https://github.com/gavu)
- Issue with cache in Sandstorm ([issue 1130](https://github.com/firefly-iii/firefly-iii/issues/1130))
- [Issue 1134](https://github.com/firefly-iii/firefly-iii/issues/1134)
- [Issue 1140](https://github.com/firefly-iii/firefly-iii/issues/1140)
- [Issue 1141](https://github.com/firefly-iii/firefly-iii/issues/1141), reported by [ErikFontanel](https://github.com/ErikFontanel)
- [Issue 1142](https://github.com/firefly-iii/firefly-iii/issues/1142)
- Removed many access rights from the demo user

# 4.6.13
- [Issue 1074](https://github.com/firefly-iii/firefly-iii/issues/1074), suggested by [MacPaille](https://github.com/MacPaille)
- [Issue 1077](https://github.com/firefly-iii/firefly-iii/issues/1077), suggested by [wtercato](https://github.com/wtercato)
- Bulk edit of transactions thanks to [vicmosin](https://github.com/vicmosin) ([issue 1078](https://github.com/firefly-iii/firefly-iii/issues/1078))
- Support for Turkish.
- [Issue 1090](https://github.com/firefly-iii/firefly-iii/issues/1090), suggested by [Findus23](https://github.com/Findus23)
- [Issue 1097](https://github.com/firefly-iii/firefly-iii/issues/1097), suggested by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1093](https://github.com/firefly-iii/firefly-iii/issues/1093), suggested by [jinformatique](https://github.com/jinformatique)
- [Issue 1098](https://github.com/firefly-iii/firefly-iii/issues/1098), suggested by [Nik-vr](https://github.com/Nik-vr)
- [Issue 972](https://github.com/firefly-iii/firefly-iii/issues/972), reported by [pjotrvdh](https://github.com/pjotrvdh)
- [Issue 1079](https://github.com/firefly-iii/firefly-iii/issues/1079), reported by [gavu](https://github.com/gavu)
- [Issue 1080](https://github.com/firefly-iii/firefly-iii/issues/1080), reported by [zjean](https://github.com/zjean)
- [Issue 1083](https://github.com/firefly-iii/firefly-iii/issues/1083), reported by [skuzzle](https://github.com/skuzzle)
- [Issue 1085](https://github.com/firefly-iii/firefly-iii/issues/1085), reported by [nicoschreiner](https://github.com/nicoschreiner)
- [Issue 1087](https://github.com/firefly-iii/firefly-iii/issues/1087), reported by [4oo4](https://github.com/4oo4)
- [Issue 1089](https://github.com/firefly-iii/firefly-iii/issues/1089), reported by [robin5210](https://github.com/robin5210)
- [Issue 1092](https://github.com/firefly-iii/firefly-iii/issues/1092), reported by [kelvinhammond](https://github.com/kelvinhammond)
- [Issue 1096](https://github.com/firefly-iii/firefly-iii/issues/1096), reported by [wtercato](https://github.com/wtercato)

# 4.6.12
- Support for Indonesian.
- New report, see [issue 384](https://github.com/firefly-iii/firefly-iii/issues/384)
- [Issue 964](https://github.com/firefly-iii/firefly-iii/issues/964) as suggested by [gavu](https://github.com/gavu)
- Greatly improved Docker support and documentation.
- [Issue 1046](https://github.com/firefly-iii/firefly-iii/issues/1046), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1047](https://github.com/firefly-iii/firefly-iii/issues/1047), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1048](https://github.com/firefly-iii/firefly-iii/issues/1048), as reported by [webence](https://github.com/webence)
- [Issue 1049](https://github.com/firefly-iii/firefly-iii/issues/1049), as reported by [nicoschreiner](https://github.com/nicoschreiner) 
- [Issue 1015](https://github.com/firefly-iii/firefly-iii/issues/1015), as reporterd by a user on Tweakers.net
- [Issue 1056](https://github.com/firefly-iii/firefly-iii/issues/1056), as reported by [repercussion](https://github.com/repercussion) 
- [Issue 1061](https://github.com/firefly-iii/firefly-iii/issues/1061), as reported by [Meizikyn](https://github.com/Meizikyn)
- [Issue 1045](https://github.com/firefly-iii/firefly-iii/issues/1045), as reported by [gavu](https://github.com/gavu)
- First code for [issue 1040](https://github.com/firefly-iii/firefly-iii/issues/1040) ([simonsmiley](https://github.com/simonsmiley))
- [Issue 1059](https://github.com/firefly-iii/firefly-iii/issues/1059), as reported by [4oo4](https://github.com/4oo4)
- [Issue 1063](https://github.com/firefly-iii/firefly-iii/issues/1063), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1064](https://github.com/firefly-iii/firefly-iii/issues/1064), as reported by [pkoziol](https://github.com/pkoziol)
- [Issue 1066](https://github.com/firefly-iii/firefly-iii/issues/1066), reported by [wtercato](https://github.com/wtercato)

# 4.6.1.1
- Import routine can scan for matching bills, [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956)
- Import will no longer scan for rules, this has become optional. Originally suggested in [issue 956](https://github.com/firefly-iii/firefly-iii/issues/956) by [gavu](https://github.com/gavu) 
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1033), as reported by [Jumanjii](https://github.com/Jumanjii)
- [Issue 1033](https://github.com/firefly-iii/firefly-iii/issues/1034), as reported by [Aquariu](https://github.com/Aquariu)
- Extra admin check for [issue 1039](https://github.com/firefly-iii/firefly-iii/issues/1039), as reported by [ocdtrekkie](https://github.com/ocdtrekkie)
- Missing translations ([issue 1026](https://github.com/firefly-iii/firefly-iii/issues/1026)), as reported by [gavu](https://github.com/gavu) and [zjean](https://github.com/zjean)
- [Issue 1028](https://github.com/firefly-iii/firefly-iii/issues/1028), reported by [zjean](https://github.com/zjean)
- [Issue 1029](https://github.com/firefly-iii/firefly-iii/issues/1029), reported by [zjean](https://github.com/zjean)
- [Issue 1030](https://github.com/firefly-iii/firefly-iii/issues/1030), as reported by [Traxxi](https://github.com/Traxxi)
- [Issue 1036](https://github.com/firefly-iii/firefly-iii/issues/1036), as reported by [webence](https://github.com/webence)
- [Issue 1038](https://github.com/firefly-iii/firefly-iii/issues/1038), as reported by [gavu](https://github.com/gavu)

# 4.6.11
- A debug page at `/debug` for easier debug.
- Strings translatable (see [issue 976](https://github.com/firefly-iii/firefly-iii/issues/976)), thanks to [Findus23](https://github.com/Findus23)
- Even more strings are translatable (and translated), thanks to [pkoziol](https://github.com/pkoziol) (see [issue 979](https://github.com/firefly-iii/firefly-iii/issues/979))
- Reconciliation of accounts ([issue 736](https://github.com/firefly-iii/firefly-iii/issues/736)), as requested by [kristophr](https://github.com/kristophr) and several others
- Extended currency list, as suggested by @emuhendis in [issue 994](https://github.com/firefly-iii/firefly-iii/issues/994)
- [Issue 996](https://github.com/firefly-iii/firefly-iii/issues/996) as suggested by [dp87](https://github.com/dp87)
- Disabled Heroku support until I get it working again.
- [Issue 980](https://github.com/firefly-iii/firefly-iii/issues/980), reported by [Tim-Frensch](https://github.com/Tim-Frensch)
- [Issue 987](https://github.com/firefly-iii/firefly-iii/issues/987), reported by [gavu](https://github.com/gavu)
- [Issue 988](https://github.com/firefly-iii/firefly-iii/issues/988), reported by [gavu](https://github.com/gavu)
- [Issue 992](https://github.com/firefly-iii/firefly-iii/issues/992), reported by [ncicovic](https://github.com/ncicovic)
- [Issue 993](https://github.com/firefly-iii/firefly-iii/issues/993), reported by [gavu](https://github.com/gavu)
- [Issue 997](https://github.com/firefly-iii/firefly-iii/issues/997), reported by [gavu](https://github.com/gavu)
- [Issue 1000](https://github.com/firefly-iii/firefly-iii/issues/1000), reported by [xpfgsyb](https://github.com/xpfgsyb)
- [Issue 1001](https://github.com/firefly-iii/firefly-iii/issues/1001), reported by [gavu](https://github.com/gavu)
- [Issue 1002](https://github.com/firefly-iii/firefly-iii/issues/1002), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1003](https://github.com/firefly-iii/firefly-iii/issues/1003), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1004](https://github.com/firefly-iii/firefly-iii/issues/1004), reported by [Aquariu](https://github.com/Aquariu)
- [Issue 1010](https://github.com/firefly-iii/firefly-iii/issues/1010)
- [Issue 1014](https://github.com/firefly-iii/firefly-iii/issues/1014), reported by [ursweiss](https://github.com/ursweiss)
- [Issue 1016](https://github.com/firefly-iii/firefly-iii/issues/1016)
- [Issue 1024](https://github.com/firefly-iii/firefly-iii/issues/1024), reported by [gavu](https://github.com/gavu)
- [Issue 1025](https://github.com/firefly-iii/firefly-iii/issues/1025), reported by [gavu](https://github.com/gavu)

# 4.6.10
- Greatly expanded Docker support thanks to [alazare619](https://github.com/alazare619)
- [Issue 967](https://github.com/firefly-iii/firefly-iii/issues/967), thanks to [Aquariu](https://github.com/Aquariu)
- Improved Sandstorm support.
- [Issue 963](https://github.com/firefly-iii/firefly-iii/issues/963), as reported by [gavu](https://github.com/gavu)
- [Issue 970](https://github.com/firefly-iii/firefly-iii/issues/970), as reported by [gavu](https://github.com/gavu)
- [Issue 971](https://github.com/firefly-iii/firefly-iii/issues/971), as reported by [gavu](https://github.com/gavu)
- Various Sandstorm.io related issues.

# 4.6.9.1
- Updated license
- Updated file list

# 4.6.9
- First version that works!

# 3.4.3
- Initial release on Sandstorm.io