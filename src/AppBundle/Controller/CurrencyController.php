<?php
/**
 * Created by PhpStorm.
 * User: Mārtiņš
 * Date: 02.08.2018
 * Time: 22:52
 */

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CurrencyController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $offset = $request->get('offset') ?: 1;
        $limit = 32;
        $repository = $this->getDoctrine()->getRepository('AppBundle:CurrencyRate');
        $recordCount = $repository->getRecordCount();
        $totalPageCount = (int) ceil($recordCount / $limit);
        $allPages = range(1, $totalPageCount);

        $pageTill = $offset + 10 > $totalPageCount ? $totalPageCount : $offset + 10;

        $pagesToShow = range($offset, $pageTill);
        $result = $repository->getDataForCurrentPage($offset, $limit);

        return $this->render('currency_table/index.html.twig',
            [
                'pages' => $pagesToShow,
                'offset' => $offset,
                'lastPage' => max($allPages),
                'firstPage' => min($allPages),
                'result' => $result
            ]);
    }
}