<?php 
namespace vaterlangen\CardDavBundle\Model;

interface BaseContactInterface extends CardDavInterface
{
	/**
	 * Get the email
	 *
	 * @return string Mailaddress
	 */
	public function getEmail();
	
	
	
	
	/**
	 * Get the mobile
	 * 
	 * @return string Mobile
	 */
	public function getMobile();
	
	/**
	 * Get the phone
	 *
	 * @return string Phone
	 */
	public function getPhone();
	
	/**
	 * Get the fax
	 *
	 * @return string Fax
	 */
	public function getFax();

}