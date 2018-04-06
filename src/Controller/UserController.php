<?php
namespace App\Controller;

use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController
{
    public function register(
        Environment $twig, 
        FormFactoryInterface $factory, 
        Request $request, 
        SessionInterface $session, 
        ObjectManager $manager, 
        UrlGeneratorInterface $urlGenerator,
        \Swift_Mailer $mailer
    ) {
        $user = new User();
        $builder = $factory->createBuilder(FormType::class, $user);
        
        $builder->add('username', TextType::class, ['label' => 'FORM.USER.USERNAME', 'attr' => ['placeholder' => 'FORM.USER.PLACEHOLDER.USERNAME']])
        ->add('firstname', TextType::class, ['label' => 'FORM.USER.FIRSTNAME', 'attr' => ['placeholder' => 'FORM.USER.PLACEHOLDER.FIRSTNAME']])
        ->add('lastname', TextType::class, ['label' => 'FORM.USER.LASTNAME', 'attr' => ['placeholder' => 'FORM.USER.PLACEHOLDER.LASTNAME']])
        ->add('email', TextType::class, ['label' => 'FORM.USER.EMAIL', 'attr' => ['placeholder' => 'FORM.USER.PLACEHOLDER.EMAIL']])
            ->add(
                'password', RepeatedType::class, 
                [
                    'type' => PasswordType::class,
                    'invalid_message' => 'The password fields must match.',
                    'first_options'  => array('label' => 'FORM.USER.PASSWORD.FIRST'),
                    'second_options' => array('label' => 'FORM.USER.PASSWORD.SECOND')
                ]
            )
            ->add('submit', SubmitType::class);
        
        $form = $builder->getForm();
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $manager->persist($user);
            $manager->flush();
            
            $message = new \Swift_Message();
            $message
                ->setFrom('wf3pm@localhost.com')
                ->setTo($user->getEmail())
                ->setSubject('Validate your account')
                ->setContentType('text/html')
                ->setBody(
                    $twig->render(
                        'mail/account_creation.html.twig',
                        ['user' => $user]
                    )
                )
                ->addPart(
                    $twig->render(
                        'mail/account_creation.txt.twig',
                        ['user' => $user]
                    ),
                    'text/plain'
                );
            
            $mailer->send($message);
            
            $session->getFlashBag()->add('info', 'Your account has been created! Check your mail-inbox.');
            
            return new RedirectResponse($urlGenerator->generate('homepage'));
            
        }
        
        return new Response(
            $twig->render('User/register.html.twig', ['formular' => $form->createView()] )
        );
    }

    public function activateUser($token, 
        ObjectManager $manager, 
        SessionInterface $session, 
        UrlGeneratorInterface $urlGenerator)
    {
        $repository = $manager->getRepository(User::class);
        $user = $repository->findOneByEmailToken($token);
        
        if(!$user) {
            throw new NotFoundHttpException('No user with the corresponding token was found.');
        }
        
        $user->setActive(true);
        $user->setEmailToken(NULL);
        
        $manager->flush();
        
        $session->getFlashBag()->add('info', 'Account has been activated!');
        
        return new RedirectResponse($urlGenerator->generate('homepage'));
    }
    
    public function availableUser(Request $request, UserRepository $userRepository)
    {
        $username = $request->request->get('username');
        
        $unavailable = false;
        if(!empty($username)){
            
            $unavailable = $userRepository->usernameExist($username);
        }
        
        return new JsonResponse(['available' => !$unavailable]);
    }
}

