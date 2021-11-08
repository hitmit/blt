<?php

namespace Drupal\Tests\alb_auth\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\alb_auth\Claims\KeyResolverInterface;
use Drupal\alb_auth\Claims\ALBClaimsExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @coversDefaultClass \Drupal\alb_auth\Claims\ALBClaimsExtractor
 * @group alb_auth
 */
class ALBClaimsExtractorTest extends UnitTestCase {

  /**
   * The test token.
   *
   * @var string
   */
  protected $token;

  /**
   * The key resolver service.
   *
   * @var \Drupal\alb_auth\Claims\KeyResolverInterface
   */
  protected $keyResolver;

  /**
   * The test request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->token = 'eyJ0eXAiOiJKV1QiLCJraWQiOiI1YjUyNTI1ZC02YmU0LTRjOGUtODI4OS'
      . '1mYTg1YjMyM2NlYTQiLCJhbGciOiJFUzI1NiIsImlzcyI6Imh0dHBzOi8vY29nbml0by1'
      . 'pZHAuZXUtd2VzdC0yLmFtYXpvbmF3cy5jb20vZXUtd2VzdC0yXzAzdUNnVkRwcyIsImNs'
      . 'aWVudCI6IjNsajVtZWxkcjN0YXV0bG4wNTVnMHM4OGVhIiwic2lnbmVyIjoiYXJuOmF3c'
      . 'zplbGFzdGljbG9hZGJhbGFuY2luZzpldS13ZXN0LTI6NTAwMzE4MzgyNTYwOmxvYWRiYW'
      . 'xhbmNlci9hcHAvSW52b3RyYVJlcC8zNWI0M2YwMjViZGU3MGNmIiwiZXhwIjoxNTM5NzA'
      . 'yNTM5fQ==.eyJzdWIiOiJiZDU4MzRhYi0yODBlLTQyYzYtODZjZS1lM2FhZWU1Njk5ZWU'
      . 'iLCJlbWFpbF92ZXJpZmllZCI6InRydWUiLCJuYW1lIjoiSm9ubmllIFJ1c3NlbGwiLCJl'
      . 'bWFpbCI6Impvbm5pZUBpbnZvdHJhLmNvIiwidXNlcm5hbWUiOiJhZG1pbiJ9.WnwtSYCK'
      . 'Hb6AeX6tih5jkLeL1f9vx9rdPR-z5eejiVFd0HewnInORfYTsV3F1D-vP8Is8jaldnm7S'
      . 'BF-QVcDDg==';

    $key = "-----BEGIN PUBLIC KEY-----\n"
      . "MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEcWnz0qwQczBjqCWrH58wG91rFtcU\n"
      . "4Ll9JnIqeIKqxqM8Ju0NIyXFhgeRj/OmuI6j1k5UbNO82rFedsPQKMv+gQ==\n"
      . "-----END PUBLIC KEY-----\n";

    $kid = '5b52525d-6be4-4c8e-8289-fa85b323cea4';

    $prophecy = $this->prophesize(KeyResolverInterface::class);
    $prophecy->getKey($kid)->willReturn($key);
    $this->keyResolver = $prophecy->reveal();

    $headerProphecy = $this->prophesize(HeaderBag::class);
    $headerProphecy->get('X_AMZN_OIDC_DATA')->willReturn($this->token);
    $requestProphecy = $this->prophesize(Request::class);
    $requestProphecy->headers = $headerProphecy->reveal();
    $this->request = $requestProphecy->reveal();
  }

  /**
   * Test getting claims from the token string.
   *
   * @covers ::getClaimsFromToken
   */
  public function testGetClaimsFromToken() {
    $claims_extractor = new ALBClaimsExtractor($this->keyResolver);
    $claims = $claims_extractor->getClaimsFromToken($this->token);
    $this->assertExtractedClaims($claims);
  }

  /**
   * Test getting claims from the Request object.
   *
   * @covers ::getClaimsFromRequest
   * @depends testGetClaimsFromToken
   */
  public function testGetClaimsFromRequest() {
    $claims_extractor = new ALBClaimsExtractor($this->keyResolver);
    $claims = $claims_extractor->getClaimsFromRequest($this->request);
    $this->assertExtractedClaims($claims);
  }

  /**
   * Asserts that claims returned by the extractor are correct.
   *
   * @param object $claims
   *   Claims returned by the extractor service.
   */
  private function assertExtractedClaims($claims) {
    $this->assertEquals($claims, (object) [
      'sub' => 'bd5834ab-280e-42c6-86ce-e3aaee5699ee',
      'email_verified' => 'true',
      'name' => 'Jonnie Russell',
      'email' => 'jonnie@invotra.co',
      'username' => 'admin',
    ]);
  }

}
