<?php 
namespace vaterlangen\CardDavBundle\Model;

interface AdditionalContactInterface extends CardDavInterface
{
	/**
	 * Get the facebook address
	 *
	 * @return string Facebook address
	 */
	public function getFacebook();
	
	
	
	
	/**
	 * Get the homepage
	 * 
	 * @return string Homepage
	 */
	public function getHomepage();
}