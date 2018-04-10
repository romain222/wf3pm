<?php
namespace App\Controller;

use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use App\Entity\Product;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\ProductRepository;
use App\Entity\Comment;
use App\Form\CommentType;

class ProductController
{
    public function addProduct(Environment $twig, 
        FormFactoryInterface $factory, 
        Request $request, 
        ObjectManager $manager, 
        SessionInterface $session)
    {
        $product = new Product();
        $builder = $factory->createBuilder(FormType::class, $product);
        $builder
            ->add('name', 
                TextType::class,
                [
                    'label' => 'FORM.PRODUCT.NAME'
                ]
                )
            ->add('description', 
                TextareaType::class,
                [
                    'required' => false,
                    'attr' => [
                        'placeholder' => 'FORM.PRODUCT.PLACEHOLDER.DESCRIPTION'
                    ]
                ]
                )
            ->add('version', 
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => '0.0.0'
                    ]
                ]
                )
            ->add('submit', SubmitType::class);
        
        $form = $builder->getForm();
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $manager->persist($product);
            $manager->flush();
            //var_dump($product);
            
            $session->getFlashBag()->add('info', 'Your product was created');
            
            return new RedirectResponse('/');
        }
            
        return new Response(
            $twig->render('Product/addProduct.html.twig', 
                ['formular' => $form->createView()]
            )
        );
    }
    
    public function listProducts(Environment $twig, ProductRepository $repository)
    {
        return new Response(
            $twig->render('Product/listProducts.html.twig', ['products' => $repository->findAll()])
        );
    }
    
    public function detailProduct(Environment $twig, ProductRepository $repository, FormFactoryInterface $formFactory)
    {
        $product = $repository->findOneById($_GET['id']);
        
        $comment = new Comment();
        $form = $formFactory->create(
            CommentType::class,
            $comment,
            ['stateless' => true]
        );
        
        return new Response(
            $twig->render('Product/detailProduct.html.twig', 
                [
                    'product' => $product,
                    'form' => $form->createView()
                ]
            )
        );
    }
}

