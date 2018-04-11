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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Ramsey\Uuid\Uuid;
use App\Entity\CommentFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


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
    
    public function detailProduct(
        Environment $twig, 
        ProductRepository $repository, 
        FormFactoryInterface $formFactory,
        Request $request,
        TokenStorageInterface $tokenStorage,
        ObjectManager $manager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $product = $repository->findOneById($_GET['id']);
        /*if (!$product) {
            throw new NotFoundHttpException();
        }*/
        
        $comment = new Comment();
        $form = $formFactory->create(
            CommentType::class,
            $comment,
            ['stateless' => true]
        );
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $commentFiles = [];
            foreach ($comment->getFiles() as $fileArray) {
                foreach ($fileArray as $file) {
                    $name = sprintf(
                        '%s.%s',
                        Uuid::uuid1(),
                        $file->getClientOriginalExtension()
                        );
                    
                    $commentFile = new CommentFile();
                    $commentFile->setComment($comment)
                        ->setMimeType($file->getMimeType())
                        ->setName($file->getClientOriginalName())
                        ->setFileUrl('/upload/'.$name);
                    
                    $tmpCommentFile[] = $commentFile;
                    
                    $file->move(
                        __DIR__.'/../../public/upload',
                        $name
                    );
                    $manager->persist($commentFile);
                }            
            }
            
            $token = $tokenStorage->getToken();
            if (!$token){
                throw new \Exception();
            }
            $user = $token->getUser();
            if (!$user){
                throw new \Exception();
            }
            
            $comment->setFiles($tmpCommentFile)
                ->setAuthor($user)
                ->setProduct($product);
            
            $manager->persist($comment);
            $manager->flush();
            
            return new RedirectResponse($urlGenerator->generate('detail_product').'?id='.$product->getId());
        }
        
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

