<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use League\OAuth1\Client\Server\Twitter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\Log\LoggerInterface;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\TwitterUser;

class TwitterAuthController extends AbstractController
{
    private $session;
    private $twitter;
    private $logger;
    private $entityManager;

    public function __construct(RequestStack $requestStack, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->session = $requestStack->getSession();

        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->logger = $logger;
        $this->entityManager = $entityManager;

        $this->twitter = new Twitter([
            'identifier'    => $_ENV['TWITTER_IDENTIFIER'],  
            'secret'        => $_ENV['TWITTER_SECRET'], 
            'callback_uri'  => $_ENV['TWITTER_CALLBACK_URI'],
        ]);
    }
    
    #[Route('/auth/twitter', name: 'twitter_login')]
    public function redirectToTwitter(): Response
    {
        try {
            if (!$this->session->isStarted()) {
                $this->session->start();
            }

            $this->session->remove('oauth_token');
            $this->session->remove('oauth_token_secret');

            $temporaryCredentials = $this->twitter->getTemporaryCredentials();
            
            $this->session->set('oauth_token', $temporaryCredentials->getIdentifier());
            $this->session->set('oauth_token_secret', $temporaryCredentials->getSecret());

            $authUrl = $this->twitter->getAuthorizationUrl($temporaryCredentials);
            return new RedirectResponse($authUrl);
        } catch (\Exception $e) {
            $this->logger->error('Twitter OAuth error: ' . $e->getMessage());
            return new Response('Error during Twitter authentication.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/auth/twitter/callback', name: 'twitter_callback')]
    public function handleTwitterCallback(Request $request): Response
    {
        try {
            $this->logger->info('Incoming Twitter callback request:', [
                'query' => $request->query->all(),
                'session' => $this->session->all()
            ]);

            $oauthToken = $request->query->get('oauth_token');
            $oauthVerifier = $request->query->get('oauth_verifier');

            if (!$oauthToken || !$oauthVerifier) {
                return new Response('Invalid OAuth request. Missing parameters.', Response::HTTP_BAD_REQUEST);
            }

            $storedOauthToken = $this->session->get('oauth_token');
            $storedOauthTokenSecret = $this->session->get('oauth_token_secret');

            if (!$storedOauthToken || !$storedOauthTokenSecret) {
                return new Response('No stored credentials found.', Response::HTTP_BAD_REQUEST);
            }

            if ($oauthToken !== $storedOauthToken) {
                return new Response('Temporary identifier mismatch. Possible security issue.', Response::HTTP_BAD_REQUEST);
            }

            $temporaryCredentials = new TemporaryCredentials();
            $temporaryCredentials->setIdentifier($storedOauthToken);
            $temporaryCredentials->setSecret($storedOauthTokenSecret);

            $tokenCredentials = $this->twitter->getTokenCredentials(
                $temporaryCredentials,
                $oauthToken,
                $oauthVerifier
            );

            $user = $this->twitter->getUserDetails($tokenCredentials);

            $this->logger->info('Twitter authentication successful.', [
                'user_nickname' => $user->nickname,
                'user_id' => $user->uid
            ]);

            $existingUser = $this->entityManager->getRepository(TwitterUser::class)->findOneBy(['twitterId' => $user->uid]);

            if (!$existingUser) {
                $twitterUser = new TwitterUser();
                $twitterUser->setTwitterId($user->uid);
                $twitterUser->setUsername($user->nickname);
                $twitterUser->setEmail($user->email ?? null);

                $this->entityManager->persist($twitterUser);
                $this->entityManager->flush();

                $this->logger->info('New user saved to database.', ['user_id' => $user->uid]);
            } else {
                $this->logger->info('User already exists in database.', ['user_id' => $user->uid]);
            }

            return $this->redirect('https://twitter.com/');
        } catch (\Exception $e) {
            $this->logger->error('Twitter callback error: ' . $e->getMessage());
            return new Response('An error occurred during Twitter authentication.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/favicon.ico', name: 'favicon')]
    public function favicon(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
