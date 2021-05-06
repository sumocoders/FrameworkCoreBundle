# Using pagination

Pagination is a nice way to handle large amounts of data over multiple pages.

## Controller

In the controller the following code should be used. If necessary a custom adapter can be used.

```
<?php

namespace SumoCoders\FrameworkCoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\NotValidCurrentPageException;

class ExampleController extends Controller
{
	/**
	 * @Route("/example/{page}", requirements={"page" = "\d+"}, defaults={"page" = "1"})
	 * @Template()
	 * 
	 * @param int $page
	 */
	public function exampleAction(int $page)
	{
		$repository = $this->getDoctrine()->getRepository('AcmeExampleBundle:Example');
		
		// returns \Doctrine\ORM\Query object
		$query = $repository->getExampleQuery();
		
		$pagerfanta = new Pagerfanta(new DoctrineORMAdapter($query));
		$pagerfanta->setMaxPerPage(45);
		
		try {
			$pagerfanta->setCurrentPage($page);
		} catch(NotValidCurrentPageException $e) {
			throw new NotFoundHttpException();
		}
		
		return ['my_pager' => $pagerfanta];
	}
}
```

## View

In the view the following code can be used

```
{% if pager.currentPageResults is not empty %}
    {% for item in my_pager.currentPageResults %}
        <ul>
            <li>{{ item.id }}</li>
        </ul>
    {% endfor %}
{% endif %}

{% if pager.haveToPaginate %}
    <div class="pagerfanta">
        {{ pagerfanta(my_pager, 'sumocoders') }}
    </div>
{% endif %}
```
