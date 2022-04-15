<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('login', name: 'login_form')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils -> getLastAuthenticationError();
        $lastUsername = $authenticationUtils -> getLastAuthenticationError();
        return $this->render('login/index.html.twig',[
            'last_username' =>$lastUsername,
            'error'          =>$error,
        ]);
    }
}
