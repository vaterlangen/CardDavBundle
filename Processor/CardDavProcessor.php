<?php

namespace vaterlangen\CardDavBundle\Processor;

use vaterlangen\CardDavBundle\Model\CardDavInterface;

use Symfony\Component\Locale\Locale;

use vaterlangen\CardDavBundle\Model\AdditionalContactInterface;
use vaterlangen\CardDavBundle\Model\BaseContactInterface;
use vaterlangen\CardDavBundle\Model\AddressInterface;
use vaterlangen\CardDavBundle\Model\PersonInterface;
use vaterlangen\CardDavBundle\Model\PhotoInterface;

use Symfony\Component\HttpFoundation\File\File;

use Symfony\Component\Intl\Intl;

use Symfony\Component\DependencyInjection\ContainerInterface;

class CardDavProcessor
{
	/**
	 *
	 * @var ContainerInterface
	 */
	protected $container;
	
	
	/**
	 *
	 * @var CardDavClient
	 */
	protected $client;
	
	/**
	 *
	 * @var string
	 */
	protected $currentConnection;
	
	/**
	 *
	 * @var array
	 */
	protected $connectionsAvail;
	
	
	/**
	 * 
	 * @param string $connectionName
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
		
		/* get new client and allow following max. 5 redirects */
		$this->client = new CardDavClient();
		$this->client->set_follow_redirects(true,5);
		
		/* get configured parameters from container */
		$this->connectionsAvail = $this->container->getParameter('vaterlangen_card_dav.addressbooks');
		$this->currentConnection = NULL;
		
		return $this;
	}
	
	/**
	 * Setup a connection by config name
	 * 
	 * @param string $connectionName
	 * @return \vaterlangen\CardDavBundle\Processor\CardDavProcessor
	 */
	public function setConnetion($connectionName)
	{
		return $this->loadConnectionData($connectionName);
	}
	
	
	/**
	 * Send vcard to server
	 *
	 * @param object $entity
	 * @param array $categories
	 * @return string UID
	 *
	 * @throws Exception
	 */
	public function set($entity, $categories = NULL)
	{
		/* check if ID interface is implemented */
		if (!$entity instanceof CardDavInterface)
		{
			throw new \InvalidArgumentException("The given entity must implement CardDavInterface!");
		}
	
		/* create vcard */
		$vCard = $this->generateFromEntity($entity,$categories);
	
		/* save vcard to server */
		$uid = $this->setByUID($vCard,$entity->getCardDavID());
	
		/* write uid to entity */
		$entity->setCardDavID($uid);
	
		return $uid;
	}
	
	/**
	 * Receive vcard from server
	 *
	 * @param object $entity
	 * @param  bool $returnRaw
	 * @return vCard|string
	 *
	 * @throws Exception
	 */
	public function get($entity, $returnRaw = false)
	{
		/* check if ID interface is implemented */
		if (!$entity instanceof CardDavInterface)
		{
			throw new \InvalidArgumentException("The given entity must implement CardDavInterface!");
		}

		/* get uid */
		$uid = $entity->getCardDavID();
		if (!$uid)
		{
			throw new \InvalidArgumentException("There is no vCard assigned to the given entity!");
		}

		return $this->getByUID($uid,$returnRaw);
	}
	
	
	/**
	 * Delete vcard from server
	 *
	 * @param object $entity
	 * @return boolean
	 *
	 * @throws Exception
	 */
	public function delete($entity)
	{
		/* check if ID interface is implemented */
		if (!$entity instanceof CardDavInterface)
		{
			throw new \InvalidArgumentException("The given entity must implement CardDavInterface!");
		}
	
		/* get uid */
		$uid = $entity->getCardDavID();
		if (!$uid)
		{
			return true;
		}
	
		/* execute delete and store to entity */
		if ($this->deleteByUID($uid))
		{
			$entity->setCardDavID(NULL);
			return true;
		}
		
		return false;
	}
	
	
	/**
	 * Delete vcard from server
	 *
	 * @param string $vCardId
	 * @return boolean
	 *
	 * @throws Exception
	 */
	public function deleteByUID($uid)
	{
		/* check for valid uid */
		if (!$uid)
		{
			return true;
		}
		
		$this->checkConnection();
		
		
		try 
		{
			$this->client->delete($uid);
		}
		catch(\Exception $e)
		{
			if (!preg_match('/http status code 404/i', $e->getMessage()))
			{
				throw $e;
			}
			
		}
		
		return true;
	}
	
	
	
	/**
	 * Get vcard from server
	 * 
	 * @param string $vCardId
	 * @param bool $returnRaw
	 * @return vCard|string
	 * 
	 * @throws Exception
	 */
	public function getByUID($uid, $returnRaw = false)
	{
		$this->checkConnection();
		
		try 
        {
			$result = $this->client->get_vcard($uid);
		}
        catch(\Exception $e)
		{
			throw $e;
		}
		
		/* check wether to return raw data or vcard */
		if ($returnRaw)
		{
			return $result;
		}
		else
		{
			$vcard = new vCard();
			$vcard->parse($result);
			
			return $vcard;
		}		
	}
	
	/**
	 * Send vcard to server
	 *
	 * @param vCard|string $vCard
	 * @param string $vCardId
	 * @return string UID
	 *
	 * @throws Exception
	 */
	public function setByUID($vCard, $uid = NULL)
	{
		if (gettype($vCard) ==! 'string' && !$vCard instanceof vCard)
		{
			throw new \InvalidArgumentException("The given vCard has to be string or vCard object! (".gettype($vCard)." given)");
		}

		
		
		$this->checkConnection();
	
		try 
        {
			$result = $this->client->add($vCard, $uid);
		}
        catch(\Exception $e)
		{
			throw $e;
		}
	
	
		return $result;
	}
	
	/**
	 * @param object $entity
	 * @param array $categories
	 * @return vCard
	 * 
	 * @throws InvalidArgumentException
	 */
	public function generateFromEntity($entity, $categories = NULL)
	{
		$implemets = 0;
		$vcard = new vCard();
		
		/* add basic header */
		$vcard->setProperty('VERSION', '3.0',0,0);
		
		/* check if entity has personal data */
		if ($entity instanceof PersonInterface)
		{
			$implemets++;
			
			$vcard->setProperty('N', $entity->getLastName(),0,0);
			$vcard->setProperty('N', $entity->getFirstName(),0,1);
			$vcard->setProperty('N', '',0,2);
			$vcard->setProperty('N', $entity->getTitle(),0,3);
			$vcard->setProperty('N', '',0,4);
			
            if ($entity->getNickName())
            {
			    $vcard->setProperty('NICKNAME', $entity->getNickName());
			}

			$vcard->setProperty('FN', $entity->getFullName());
    		
			$vcard->setProperty('GENDER', strtoupper($entity->getGenderCode()));
			
            if ($entity->getBirthday())
            {
			    $vcard->setProperty('BDAY', $entity->getBirthday()->format('Y-m-d'));
            }
		}
		
		
		/* check if entity has address data */
		if ($entity instanceof AddressInterface)
		{
			$implemets++;
			
            $countrylist = Intl::getRegionBundle()->getCountryNames();

            
            $country = $entity->getCountry();
            if (strlen($country) === 2)
            {
                $country = $countrylist[strtoupper($country)];
            }

#			$country = strlen($entity->getCountry()) === 2 ? 
#					   ($entity->getCountry() === 'DE' ? "Deutschland" : $entity->getCountry()) : 
#					   $entity->getCountry();
			
			$vcard->setProperty('ADR;TYPE=HOME', '',0,0);
			$vcard->setProperty('ADR;TYPE=HOME', '',0,1);
			$vcard->setProperty('ADR;TYPE=HOME', $entity->getStreet().' '.$entity->getNumber(),0,2);
			$vcard->setProperty('ADR;TYPE=HOME', $entity->getCity(),0,3);
			$vcard->setProperty('ADR;TYPE=HOME', $entity->getProvince(),0,4);
			$vcard->setProperty('ADR;TYPE=HOME', $entity->getZipcode(),0,5);
			$vcard->setProperty('ADR;TYPE=HOME', $country,0,6);
			
			$vcard->setProperty('LABEL;TYPE=HOME', $entity->getStreet().' '.$entity->getNumber()."\n".
										 $entity->getZipcode().' '.$entity->getCity()."\n".
										 strtoupper($country));

		}
		
		
		/* check if entity has base contact data */
		if ($entity instanceof BaseContactInterface)
		{
			$implemets++;
		
            if ($entity->getMobile())	
            {
			    $vcard->setProperty('TEL;TYPE=CELL', $entity->getMobile());
			}

            if ($entity->getPhone())
            {
                $vcard->setProperty('TEL;TYPE=HOME', $entity->getPhone());
			}

            if ($entity->getFax())
            {
                $vcard->setProperty('TEL;TYPE=FAX', $entity->getFax());
			}


            if ($entity->getEmail())
            {
			    $vcard->setProperty('EMAIL;TYPE=HOME', $entity->getEmail());
            }
		}
		
		
		/* check if entity has additional contact data */
		if ($entity instanceof AdditionalContactInterface)
		{
			$implemets++;
			
            if ($entity->getFacebook())
            {
			    $vcard->setProperty('FBURL', $entity->getFacebook());
            }
            if ($entity->getHomepage())
            {
			    $vcard->setProperty('URL', $entity->getHomepage());
            }
		}

        /* check if entity has image data */
    	if ($entity instanceof PhotoInterface)
		{
            $photo = $entity->getPhoto();
            #throw new \InvalidArgumentException(gettype($photo)); 
            if ($photo)
            {
                /* process attached files */
                if ($photo instanceof File)
                {
                    $type = toUpper($photo->guessExtension());
                    $type = $type ? $type : toUpper($photo->getExtension());

			        $vcard->setProperty('PHOTO;TYPE='.$type.';ENCODING=B',$this->encodeBase64($photo));
                }
                /* process urls */
                elseif (is_string($photo) && filter_var($photo, FILTER_VALIDATE_URL))
                {
                    if (!$this->url_exists($photo))
                    {
                        throw new \InvalidArgumentException("The given photo url '$photo' seem to be unavailable!");
                    }
                    $vcard->setProperty('PHOTO;VALUE=URI',$photo);
                }else{
                    throw new \InvalidArgumentException("Unkown phptp type '".gettype($photo)."'!");
                }
            }
        }
		
		
		/* check categories parameter */
		if (gettype($categories) === 'string')
		{
			$categories = array($categories);
		} 

		if (!is_array($categories))
		{
			$categories = array();
		}
		
		/* check categories from entity */
		$entityCategories = $entity->getCategories();
		if (gettype($entityCategories) === 'string')
		{
			$entityCategories = array($entityCategories);
		}
		
		if (!is_array($entityCategories))
		{
			$entityCategories = array();
		}

		/* merge categories */
		$categories = array_merge($categories,$this->getDefaultCategories());
		$categories = array_merge($categories,$entityCategories);
		
		/* write non-empty categoriesd to vcard */
		if (count($categories))
		{
			$vcard->setProperty('CATEGORIES', join(',',$categories));
		}
		
		
		/* check if entity was valid */
		if ($implemets === 0)
		{
			throw new \InvalidArgumentException("The given entity must implement at least one supported interface!");
		}
		
		return $vcard;
	}
	
	/**
	 * Ceck if connection data is loaded
	 * 
	 * @param string $connectionName
	 * @return boolean
	 */
	public function isConnected($connectionName = NULL)
	{
		return $connectionName === NULL ? ($this->currentConnection !== NULL) : ($this->currentConnection === $connectionName);
	}
	
	/**
	 * 
	 * @throws \BadFunctionCallException
	 */
	private function checkConnection()
	{
		/* check if connection is loaded */
		if (!$this->isConnected())
		{
			throw new \BadFunctionCallException("");
		}
		
		/* check if server is available */
		if (!$this->client->check_connection())
		{
			throw new \Exception("The connection to the server failed!");
		}
	}
	
	
	/**
	 * Load requested connection data from configuration file 
	 * 
	 * @param string $connectionName
	 * @throws \InvalidArgumentException
	 */
	private function loadConnectionData($connectionName)
	{
		/* check if connection already loaded */
		if ($this->currentConnection === $connectionName)
		{
			return $this;
		}
		 
		
		/* check if requested addressbook config does exist */
		if (!key_exists($connectionName, $this->connectionsAvail))
		{
			throw new \InvalidArgumentException("The requested addressbook config for '$connectionName' does not exist!\r\n".
					"Existing configurations: ".join(', ', array_keys($this->connectionsAvail)));
		}
		 
		/* select requested connection */
		$adb = $this->connectionsAvail[$connectionName];
		
		$port = (0 !== $adb['ssl'] && is_numeric($adb['ssl'])) ? $adb['ssl'] : NULL;
		if ($port && 65535 < $port )
		{
			throw new \InvalidArgumentException("The given port '$port' is invalid!");
		}
		
		/* build url from config */
		$url = 'http'.($adb['ssl'] ? 's' : '').'://'.$adb['server'].($port ? ':'.$port : '').'/'.$adb['resource'].'/';
		
		/* set basic auth and url */
		$this->client->set_auth($adb['user'], $adb['password']);
		$this->client->set_url($url);
		
		/* store connection name */
		$this->currentConnection = $connectionName;

		return $this;
	}
	
	private function getDefaultCategories($connectionName = NULL)
	{
		$t = $this->connectionsAvail[($connectionName === NULL ? $this->currentConnection : $connectionName) ];
		
		return $t['categories'];
	}

    /**
	 * Convert file to base64 encoded string
	 * 
	 * @param File $file
	 * @return string
     * @throws Exception
	 */
    private function encodeBase64(File $file) 
    {
        $buffer = "";
        $path = $file->getRealPath();

		$fp = @fopen($path, "r");
		if (!$fp) 
        {
			throw new \Exception("Unable to read '$path'!");
		} 
        else 
        {
			while (!feof($fp)) {
				$buffer .= fgets($fp, 4096);
			}
		}
		@fclose($fp);

		return base64_encode($buffer);
	}

    private function url_exists($url) {
        if (!curl_init($url)) return false;
        return true;
    }

}
