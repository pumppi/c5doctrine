<?php
Loader::library('doctrine', 'doctrine');
class DashboardDoctrineController extends Controller {
	
		
	
	public function view(){
		
		$manager = DoctrineC5Adapter::getEntityManager();
		$posts = $manager->getRepository('Collapick\Models\Post')->findAll();
		$this->set('posts', $posts);
		return $this->render('/dashboard/doctrine/list');
	}
	
	
	public function add_post(){
		
		if($this->isPost()){
			$error = Loader::helper('validation/error');
			$manager = DoctrineC5Adapter::getEntityManager();
			
			$title = $this->post('title');
			$body = $this->post('body');
			
			$post = new Collapick\Models\Post();
			$post->setTitle($title);
			$post->setBody($body);
			
			$validator = \Symfony\Component\Validator\Validation::createValidatorBuilder()
					->enableAnnotationMapping()
					->getValidator();

			/* @var $violations Symfony\Component\Validator\ConstraintViolationList */
			$violations = $validator->validate($post);
			
			foreach ($violations->getIterator() as $violation) {
				/* @var $violation Symfony\Component\Validator\ConstraintViolation */
				$error->add($violation->getMessage());
			}
			
			if (!$error->has()) {
				$manager->persist($post);
				$manager->flush();
				return $this->redirect('/dashboard/doctrine');
			}
			$this->set('error', $error);
			
		}
		
		
		return $this->render('/dashboard/doctrine/add');
	}
	
	
}