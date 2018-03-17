<?php 
namespace vaterlangen\CardDavBundle\Model;

interface CardDavInterface
{
	/**
	 * Get the unique identifier
	 *
	 * @return string ID
	 */
	public function getCardDavID();
	
	
	/**
	 * Set the unique identifier
	 *
	 * @param string $id
	 */
	public function setCardDavID($id);
	
	
	/**
	 * Set the categories
	 *
	 * @return array Categories to set
	 */
	public function getCategories();
}