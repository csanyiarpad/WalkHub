<?php
/**
 * @file
 * Test Walkthrough playing processes.
 */


require_once './vendor/autoload.php';
require_once './wt_selenium_testcase.inc';

class WalkthroughPlaying extends WalkhubSeleniumTestCase {

  public function provider() {
    // scan walkthroughs/ directory
    $allcontent = scandir('./walkthroughs');
    $relevantcontent = array_diff($allcontent, array('..', '.'));
    $converted = array_map(function ($item) {
      return array($item);
    }, $relevantcontent);
    return array_values($converted);
  }

  /**
   * @dataProvider provider
   */
  public function testWalkthrough($filename) {
    $test = file_get_contents("walkthroughs/{$filename}");

    $title = $this->randomString();

    $account = $this->createUser();
    $this->login($account->username, $account->password);

    $this->url('walkthrough/import');

    mock($this->byId('edit-selenium-code'))->value($test);
    mock($this->byId('edit-next'))->click();

    mock($this->byId('edit-title'))->value($title);
    mock($this->byId('edit-body'))->value($this->randomString());
    $this->select($this->byId('edit-severity'))->selectOptionByLabel('does not change anything (tour)');
    mock($this->byId('edit-save'))->click();

    // Walkthrough has been created.
    $this->assertEquals($title, mock($this->byId('page-title'))->text());

    // Test screenshotting flag.
    $this->byLinkText('Create screenshots')->click();

    mock($this->byLinkText('Start walkthrough'))->click();
    $wu = new PHPUnit_Extensions_Selenium2TestCase_WaitUntil($this);
    $self = $this;
    $wu->run(function () use ($self) {
      return ($item = $self->byXPath('//div[contains(@class, "walkthrough-start-dialog")]//button[@type="button"]')) ? $item : NULL;
    }, 30000);
    mock($this->byXPath('//div[contains(@class, "walkthrough-start-dialog")]//button[@type="button"]'))->click();

    $this->frame($this->byCssSelector('#ui-id-2'));

    $steps = $this->numberOfSteps($test);
    for ($i = 0; $i <= $steps; $i++) {
      mock($this->byCssSelector('.wtbubble-next'))->click();
    }

    $finish_button = $this->byLinkText('Finish');
    $this->assertTrue((bool)$finish_button, 'Walkthrough did not finish correctly');
  }

  protected function numberOfSteps($test) {
    $doc = new DOMDocument();
    $doc->loadHTML($test);
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query('//tbody/tr');
    return $elements ? $elements->length-2 : NULL;
  }
}

