<?php

final class ConduitSearchFuture
  extends FutureAgent {

  private $conduitEngine;
  private $method;
  private $constraints;

  private $objects = array();
  private $cursor;

  public function setConduitEngine(ArcanistConduitEngine $conduit_engine) {
    $this->conduitEngine = $conduit_engine;
    return $this;
  }

  public function getConduitEngine() {
    return $this->conduitEngine;
  }

  public function setMethod($method) {
    $this->method = $method;
    return $this;
  }

  public function getMethod() {
    return $this->method;
  }

  public function setConstraints($constraints) {
    $this->constraints = $constraints;
    return $this;
  }

  public function getConstraints() {
    return $this->constraints;
  }

  public function isReady() {
    if ($this->hasResult()) {
      return true;
    }

    $futures = $this->getFutures();
    $future = head($futures);

    if (!$future) {
      $future = $this->newFuture();
    }

    if (!$future->isReady()) {
      $this->setFutures(array($future));
      return false;
    } else {
      $this->setFutures(array());
    }

    $result = $future->resolve();

    foreach ($this->readResults($result) as $object) {
      $this->objects[] = $object;
    }

    $cursor = idxv($result, array('cursor', 'after'));

    if ($cursor === null) {
      $this->setResult($this->objects);
      return true;
    }

    $this->cursor = $cursor;
    $future = $this->newFuture();
    $this->setFutures(array($future));

    return false;
  }

  private function newFuture() {
    $engine = $this->getConduitEngine();

    $method = $this->getMethod();
    $constraints = $this->getConstraints();

    $parameters = array(
      'constraints' => $constraints,
    );

    if ($this->cursor !== null) {
      $parameters['after'] = (string)$this->cursor;
    }

    $conduit_call = $engine->newCall($method, $parameters);
    $conduit_future = $engine->newFuture($conduit_call);

    return $conduit_future;
  }

  private function readResults(array $data) {
    return idx($data, 'data');
  }

}