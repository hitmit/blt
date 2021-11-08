<?php

namespace Drupal\alb_auth\Claims;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\Converter\StandardConverter;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Checker\InvalidHeaderException;
use Symfony\Component\HttpFoundation\Request;

/**
 * The claims extractor service.
 */
class ALBClaimsExtractor implements ClaimsExtractorInterface {

  /**
   * ALB key resolver service.
   *
   * @var \Drupal\alb_auth\Claims\KeyResolverInterface
   */
  protected $keyResolver;

  /**
   * Constructs new ALBClaimsExtractor object.
   *
   * @param \Drupal\alb_auth\Claims\KeyResolverInterface $key_resolver
   *   ALB key resolver service.
   */
  public function __construct(KeyResolverInterface $key_resolver) {
    $this->keyResolver = $key_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimsFromRequest(Request $request) {
    // @TODO: Check if the token is available, throw exception otherwise.
    $token = $request->headers->get('X_AMZN_OIDC_DATA');
    return $this->getClaimsFromToken($token);
  }

  /**
   * {@inheritdoc}
   */
  public function getClaimsFromToken($token) {
    // TODO: Define Drupal services for objects below, use DI. Let Drupal
    // TODO: initialize everything for us.
    $algManager = AlgorithmManager::create([new ES256()]);
    $verifier = new JWSVerifier($algManager);
    $jsonConverter = new StandardConverter();
    $serializer = new CompactSerializer($jsonConverter);

    // TODO: check other header fields eg aud, iss.
    $headerCheckerManager = HeaderCheckerManager::create(
        [new AlgorithmChecker(['ES256'])],
        [new JWSTokenSupport()]
    );

    $jws = $serializer->unserialize($token);
    try {
      // Check the header; this will throw an exception if the check fails.
      $headerCheckerManager->check($jws, 0);

      // Get the key with which to validate the signature.
      $signature = $jws->getSignature(0);
      $kid = $signature->getProtectedHeaderParameter('kid');
      $keydata = $this->keyResolver->getKey($kid);

      // Verify the signature.
      $key = JWKFactory::createFromKey($keydata, NULL, ['use' => 'sig']);
      if ($verifier->verifyWithKey($jws, $key, 0)) {
        return json_decode($jws->getPayload());
      }
      else {
        \Drupal::logger('alb_auth')->error('Failed to verify signature');
      }
    }
    catch (InvalidHeaderException $e) {
      \Drupal::logger('alb_auth')->error('Invalid header');
    }
    catch (\Exception $e) {
      \Drupal::logger('alb_auth')->error('Exception getting claims: @message', ['@message' => $e->getMessage()]);
    }
    return [];
  }

}
