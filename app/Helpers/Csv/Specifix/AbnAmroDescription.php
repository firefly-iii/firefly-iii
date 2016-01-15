<?php

namespace FireflyIII\Helpers\Csv\Specifix;

use Log;

/**
 * Parses the description from txt files for ABN AMRO bank accounts. 
 * 
 * Based on the logic as described in the following Gist:
 * https://gist.github.com/vDorst/68d555a6a90f62fec004
 *
 * @package FireflyIII\Helpers\Csv\Specifix
 */
class AbnAmroDescription
{
    /** @var array */
    protected $data;

    /** @var array */
    protected $row;


    /**
     * @return array
     */
    public function fix()
    {
        $this->handleAmount();
        $this->parseSepaDescription();

        return $this->data;

    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @param array $row
     */
    public function setRow($row)
    {
        $this->row = $row;
    }
    
    protected function handleAmount() {
        $this->data['amount'] = floatval(str_replace(',', '.', $this->row[6]));
    }

    /**
     * Parses the current description in SEPA format
     * @return boolean true if the description is SEPA format, false otherwise
     */
    protected function parseSepaDescription()
    {
        // See if the current description is formatted as a SEPA plain description
        if( preg_match( "/^SEPA(.{28})/", $this->data[ "description" ], $matches ) ) {
            Log::debug('AbnAmroSpecifix: Description is structured as SEPA plain description.');
            $type = trim($matches[1]);
            
            // SEPA plain descriptions contain several key-value pairs, split by a colon
            preg_match_all( "/([A-Za-z]+(?=:\s)):\s([A-Za-z 0-9.-]+(?=\s))/", $this->data[ "description" ], $matches, PREG_SET_ORDER );
            
            foreach( $matches as $match ) {
                $key = $match[1];
                $value = trim($match[2]);
                
                switch( strtoupper($key) ) {
                    case 'OMSCHRIJVING':
                        $this->data['description'] = $value;
                        break;
                    case 'NAAM':
                        $this->data['opposing-account-name'] = $value;
                        break;
                    case 'IBAN':
                        $this->data['opposing-account-iban'] = $value;
                        break;
                    default:
                        // Ignore the rest
                }
            }
            
            // Add the type to the description
            $this->data['description'] .= ' (' . $type . ')';
            
            return true;
        }
        
        return false;
    }
    
/***
 * 
def ParseDescription(desc):
	values = None
	### SEPA PLAIN:    SEPA iDEAL                       IBAN: NL12RABO0121212212        BIC: RABONL2U                    Naam: Silver Ocean B.V.         Omschrijving: 1232138 1232131233 412321 iBOOD.com iBOOD.com B.V. Kenmerk: 12-12-2014 21:03 002000 0213123238
	sepa = re.findall(r"(?P<SEPA>^SEPA.{28})", desc, re.I)
	if (sepa):
		values = {}
		value = sepa[0]
		values["TRTP"] = value.strip()
		values["EREF"] = ""
		values["REMI"] = ""
		sepa = re.findall(r"(?P<NAME>[A-Za-z]+(?=:\s)):\s(?P<VALUE>[A-Za-z 0-9.-]+(?=\s))", desc, re.I) 
		for line in sepa:
			key = line[0]
			if key.upper() == 'OMSCHRIJVING':
				key = 'REMI'
			if key.upper() == 'KENMERK':
				key = 'EREF'
			if key.upper() == 'NAAM':
				key = 'NAME'
			value = line[1]
			values[key] = value.strip()
			# print (values)
			# continue
		if len(values["REMI"]) > 19:
				values["REMI"] = values["REMI"][0:18] + values["REMI"][19:]
		if values["REMI"] == "":
			values["REMI"] = values["TRTP"]

	### TRTP ENCODED: /TRTP/SEPA OVERBOEKING/IBAN/NL23ABNA0000000000/BIC/ABNANL2A/NAME/baasd dsdsT CJ/REMI/Nullijn/EREF/NOTPROVIDED
	trtp = re.findall(r"\/(?P<NAME>[A-Z]{3,4})\/(?P<VALUE>.*?(?:(?=\/[A-Z]{3,4}\/)|$))",desc, re.I)
	if (trtp):
		values = {}
		values["EREF"] = ""
		values["REMI"] = ""
		for line in trtp:
			key = line[0]
			value = line[1]
			values[key] = value.strip()
			# print (values)
			# continue
		if values["REMI"] == "":
			values["REMI"] = values["TRTP"]
			
	### BEA: BEA   NR:00AJ01   31.01.01/19.54 Van HarenSchoenen132 UDE,PAS333			
	trtp = re.findall(r"(?P<TRTP>[BG]EA) +(?P<EREF>NR:[a-zA-Z:0-9]+) +(?P<DATE>[0-9.\/]+) +(?P<NAAM>[^,]*)", desc, re.I)
	if (trtp):
		values = {}
		values["TRTP"] = str(trtp[0][0]).strip()
		values["NAME"] = str(trtp[0][3]).strip()
		values["EREF"] = str(trtp[0][1]).strip()
		values["DATE"] = str(trtp[0][2]).strip()
		values["REMI"] = values["TRTP"] + " " + values["NAME"]
		# print (values)
		# continue
		
	### OLD:  12.21.22.222                    BNP aaaaaaa aaaaaa SCH          BETALINGSKENM.  2323233232323323 MAAND* APRIL 01                 REF* 1212121-42-41    
	trtp = re.findall(r"^ ?(?P<IBAN>[0-9.]{12,15})\W+(?P<NAAM>.{32})", desc, re.I)
	if (trtp):
		values = {}
		values["TRTP"] = "OLD"
		values["IBAN"] = str(trtp[0][0]).strip()
		values["NAME"] = str(trtp[0][1]).strip()
		values["EREF"] = ""
		values["REMI"] = values["TRTP"] + " " + values["NAME"]
		# print (values)
		# continue
	### ABN AMRO Bank N.V.               Prive pakket                3,25
	abn = re.findall(r"^ABN AMRO.{24} (?P<DESC>.*)", desc, re.I)
	if (abn):
		values = {}
		values["TRTP"] = "ABN AMBRO"
		values["NAME"] = "ABN AMBRO"
		values["EREF"] = str(abn[0]).strip()
		values["REMI"] = values["EREF"]
		# print (values)
		# continue
	if (values == None):
		# print ("Unkown: ### %s ###" % ( desc ))
		return None
	return values * 
 * 
 */

}
