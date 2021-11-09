<?php

namespace Drupal\media_entity_download_inline\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Media Entity Download Inline event subscriber.
 */
class MediaEntityDownloadInlineSubscriber implements EventSubscriberInterface {

  const INLINE_EXTENSIONS = ['pdf'];

  /**
   * Kernel request event handler.
   *
   * This adds the `inline` query parameter needed to open pdfs in the browser
   * rather than them being downloaded.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {
    $request = $event->getRequest();
    $uri = $request->getUri();
    $extension = pathinfo($uri, PATHINFO_EXTENSION);
    if(in_array($extension, self::INLINE_EXTENSIONS)) {
      $request->query->set('inline', true);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest']
    ];
  }

}
