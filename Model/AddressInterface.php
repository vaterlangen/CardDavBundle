<?php 
namespace vaterlangen\CardDavBundle\Model;

interface AddressInterface extends CardDavInterface
{
	/**
	 * Get the street name
	 * 
	 * @return string Streetname
	 */
	public function getStreet();
	
	/**
	 * Get the number
	 *
	 * @return string Number
	 */
	public function getNumber();
	
	/**
	 * Get the addition
	 *
	 * @return string Addition
	 */
	public function getAddition();
	
	
	
	
	/**
	 * Get the city
	 *
	 * @return string City
	 */
	public function getCity();
	
	/**
	 * Get the zip code
	 *
	 * @return string Zip-Code
	 */
	public function getZipcode();
	
	
	
	/**
	 * Get the state name
	 *
	 * @return string Statename
	 */
	public function getProvince();
	
	
	
	/**
	 * Get the country code
	 *
	 * @return string Country
	 */
	public function getCountry();
	
	
	
	
	
}