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

class TwitterAuthController extends AbstractController
{
    private $session;
    private $twitter;
    private $logger;

    public function __construct(RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->session = $requestStack->getSession();

        // Ensure session is started
        if (!$this->session->isStarted()) {
            $this->session->start();
        }

        $this->logger = $logger;

        // Use OAuth 1.0a credentials
        $this->twitter = new Twitter([
            'identifier'    => $_ENV['TWITTER_IDENTIFIER'],  
            'secret'        => $_ENV['TWITTER_SECRET'], 
            'callback_uri'  => $_ENV['TWITTER_CALLBACK_URI'],
            'version'      => '1.1' 
        ]);
    }
    
    #[Route('/auth/twitter', name: 'twitter_login')]
    public function redirectToTwitter(): Response
    {
        try {
            if (!$this->session->isStarted()) {
                $this->session->start();
            }

            // Clear previous OAuth tokens
            $this->session->remove('oauth_token');
            $this->session->remove('oauth_token_secret');

            // Request temporary credentials from Twitter
            $temporaryCredentials = $this->twitter->getTemporaryCredentials();

            // Store credentials in session
            $this->session->set('oauth_token', $temporaryCredentials->getIdentifier());
            $this->session->set('oauth_token_secret', $temporaryCredentials->getSecret());

            // Debug session state
            $this->logger->info('Session before setting OAuth tokens:', [
                'isStarted' => $this->session->isStarted(),
                'oauth_token' => $this->session->get('oauth_token'),
                'oauth_token_secret' => $this->session->get('oauth_token_secret')
            ]);

            // Generate authorization URL
            $authUrl = $this->twitter->getAuthorizationUrl($temporaryCredentials);
            $this->logger->info('Redirecting to Twitter authorization URL:', ['url' => $authUrl]);

            // Redirect to Twitter login
            return new RedirectResponse($authUrl);
        } catch (\Exception $e) {
            $this->logger->error('Twitter OAuth error: ' . $e->getMessage());
            return new Response('Error during Twitter authentication.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Handles the Twitter OAuth callback
     */
    #[Route('/auth/twitter/callback', name: 'twitter_callback')]
    public function handleTwitterCallback(Request $request): Response
    {
        try {
            // Log full request data for debugging
            $this->logger->info('Incoming Twitter callback request:', [
                'query' => $request->query->all(),
                'session' => $this->session->all()
            ]);

            $oauthToken = $request->query->get('oauth_token');
            $oauthVerifier = $request->query->get('oauth_verifier');

            // Log received tokens for debugging
            $this->logger->info('Received OAuth tokens:', [
                'oauth_token' => $oauthToken,
                'oauth_verifier' => $oauthVerifier
            ]);

            if (!$oauthToken || !$oauthVerifier) {
                $this->logger->error('Invalid OAuth request. Missing parameters.', ['query' => $request->query->all()]);
                return new Response('Invalid OAuth request. Missing parameters.', Response::HTTP_BAD_REQUEST);
            }

            // Retrieve stored credentials from session
            $storedOauthToken = $this->session->get('oauth_token');
            $storedOauthTokenSecret = $this->session->get('oauth_token_secret');

            // Log session state before verification
            $this->logger->info('Session at callback:', [
                'oauth_token' => $storedOauthToken,
                'oauth_token_secret' => $storedOauthTokenSecret
            ]);
            
            if (!$storedOauthToken || !$storedOauthTokenSecret) {
                $this->logger->error('No stored credentials found.');
                return new Response('No stored credentials found.', Response::HTTP_BAD_REQUEST);
            }

            // Validate that the returned token matches the stored token
            if ($oauthToken !== $storedOauthToken) {
                $this->logger->error('Temporary identifier mismatch. Possible session issue.', [
                    'received_token' => $oauthToken,
                    'stored_token' => $storedOauthToken
                ]);
                return new Response('Temporary identifier mismatch. Possible security issue.', Response::HTTP_BAD_REQUEST);
            }

            // Reconstruct temporary credentials
            $temporaryCredentials = new TemporaryCredentials();
            $temporaryCredentials->setIdentifier($storedOauthToken);
            $temporaryCredentials->setSecret($storedOauthTokenSecret);

            // Exchange temporary credentials for token credentials
            $tokenCredentials = $this->twitter->getTokenCredentials(
                $temporaryCredentials,
                $oauthToken,
                $oauthVerifier
            );

            // Fetch user details
            $user = $this->twitter->getUserDetails($tokenCredentials);

            // Log successful authentication
            $this->logger->info('Twitter authentication successful.', [
                'user_nickname' => $user->nickname,
                'user_id' => $user->uid
            ]);

            return $this->redirect('https://twitter.com/');

            return new Response('Twitter login successful for ' . $user->nickname);
        } catch (\Exception $e) {
            // Log error details
            $this->logger->error('Twitter callback error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return new Response('An error occurred during Twitter authentication.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }  
    /**
     * Handles favicon.ico requests to prevent 404 errors
     */
    #[Route('/favicon.ico', name: 'favicon')]
    public function favicon(): Response
    {
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
