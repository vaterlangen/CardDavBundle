<?php 
namespace vaterlangen\CardDavBundle\Model;

interface PersonInterface extends CardDavInterface
{
	/**
	 * Get the gender
	 *
	 * @return string Gender (M|F|J)
	 */
	public function getGenderCode();
	
	
	
	
	/**
	 * Get the last name
	 * 
	 * @return string Lastname
	 */
	public function getLastName();
	
	/**
	 * Get the first name
	 *
	 * @return string Firstname
	 */
	public function getFirstName();
	
	/**
	 * Get the academic degree
	 *
	 * @return string Academic degree
	 */
	public function getTitle();

	
	
	/**
	 * Get the full name
	 *
	 * @return string Fullname
	 */
	public function getFullName();
	
	
	/**
	 * Get the nickname
	 *
	 * @return string Nickname
	 */
	public function getNickName();
	
	
	
	
	/**
	 * Get the birthday
	 *
	 * @return \Date Birthday
	 */
	public function getBirthday();
	
}