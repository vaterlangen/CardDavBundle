<?php 
namespace vaterlangen\CardDavBundle\Model;
use Symfony\Component\HttpFoundation\File\File;

interface PhotoInterface extends CardDavInterface
{
	/**
	 * Get the attached photo
	 *
	 * @return File|string Photo|URL
	 */
	public function getPhoto();
	
}
