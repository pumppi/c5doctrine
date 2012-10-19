<?php
namespace Collapick\Models;
	
use Doctrine\ORM\Mapping AS ORM;
use Symfony\Component\Validator\Constraints AS Assert;

/**
 * 
 * @ORM\Entity
 * @ORM\Table(name = "post_test_table")
 */
class Post {
    
	/** 
	 * @ORM\Id 
	 * @ORM\GeneratedValue 
	 * @ORM\Column(type="integer") 
	 **/
    protected $id;
	
    /** 
	 * @Assert\NotBlank(message="Title can't be blank")
     * @Assert\MinLength(
     *     limit=2,
     *     message="Title must have at least {{ limit }} characters."
     * )
	 * @ORM\Column(type="string") 
	 **/
    protected $title;
	
    /**
	 * @ORM\Column(type="text") 
	 * **/
    protected $body;
	
	
	
	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
	}

	public function getTitle() {
		return $this->title;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getBody() {
		return $this->body;
	}

	public function setBody($body) {
		$this->body = $body;
	}
	
}
