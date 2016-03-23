<?php

namespace OroB2B\Bundle\SaleBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ActionBundle\Model\ActionData;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteToOrderType;
use OroB2B\Bundle\SaleBundle\Entity\Quote;

class QuoteController extends Controller
{
    /**
     * @Route("/view/{id}", name="orob2b_sale_quote_frontend_view", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="orob2b_sale_quote_frontend_view",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Quote $quote
     * @return array
     */
    public function viewAction(Quote $quote)
    {
        return [
            'data' => [
                'quote' => $quote
            ]
        ];
    }

    /**
     * @Route("/", name="orob2b_sale_quote_frontend_index")
     * @Layout(vars={"entity_class"})
     * @Acl(
     *      id="orob2b_sale_quote_frontend_index",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="VIEW",
     *      group_name="commerce"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orob2b_sale.entity.quote.class')
        ];
    }

    /**
     * @Route("/info/{id}", name="orob2b_sale_quote_frontend_info", requirements={"id"="\d+"})
     * @Template("OroB2BSaleBundle:Quote/Frontend/widget:info.html.twig")
     * @AclAncestor("orob2b_sale_quote_frontend_view")
     *
     * @param Quote $quote
     * @return array
     */
    public function infoAction(Quote $quote)
    {
        return [
            'entity' => $quote
        ];
    }

    /**
     * @Route("/choice/{id}", name="orob2b_sale_quote_frontend_choice", requirements={"id"="\d+"})
     * @Layout()
     * @Acl(
     *      id="orob2b_sale_quote_frontend_choice",
     *      type="entity",
     *      class="OroB2BSaleBundle:Quote",
     *      permission="ACCOUNT_VIEW",
     *      group_name="commerce"
     * )
     * @ParamConverter("quote", options={"repository_method" = "getQuote"})
     *
     * @param Request $request
     * @param Quote $quote
     * @return array
     */
    public function choiceAction(Request $request, Quote $quote)
    {
        $form = $this->createForm(QuoteToOrderType::NAME, $quote);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $actionData = $this->container->get('oro_action.manager')->execute(
                'orob2b_sale_frontend_quote_accept_and_submit_to_order',
                new ActionData([
                    'quote' => $quote,
                    'selectedItems' => $form->getData()
                ])
            );

            $redirectUrl = $actionData->getRedirectUrl();
            if ($redirectUrl) {
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse(['redirectUrl' => $redirectUrl]);
                } else {
                    return $this->redirect($redirectUrl);
                }
            }
        }

        return [
            'data' => [
                'data' => $quote,
                'form' => $form->createView()
            ]
        ];
    }
}
