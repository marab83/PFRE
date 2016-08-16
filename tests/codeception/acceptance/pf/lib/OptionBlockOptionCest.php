<?php 
/* $pfre: OptionBlockOptionCest.php,v 1.2 2016/08/16 05:22:24 soner Exp $ */

/*
 * Copyright (c) 2016 Soner Tari.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. All advertising materials mentioning features or use of this
 *    software must display the following acknowledgement: This
 *    product includes software developed by Soner Tari
 *    and its contributors.
 * 4. Neither the name of Soner Tari nor the names of
 *    its contributors may be used to endorse or promote products
 *    derived from this software without specific prior written
 *    permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

require_once ('Rule.php');

class OptionBlockOptionCest extends Rule
{
	protected $type= 'Option';
	protected $ruleNumber= 21;
	protected $ruleNumberGenerated= 29;
	protected $sender= 'option';

	protected $origRule= 'set block-policy drop # Test';
	protected $expectedDispOrigRule= 'block-policy: drop Test e u d x';

	protected $modifiedRule= 'set block-policy return # Test1';
	protected $expectedDispModifiedRule= 'block-policy: return Test1 e u d x';

	function __construct()
	{
		parent::__construct();

		$this->dLink= NULL;
	}

	protected function loadTestRules(AcceptanceTester $I)
	{
		parent::loadTestRules($I);

		$I->click('Rules');
		$I->wait(STALE_ELEMENT_INTERVAL);

		// Delete the existing Option rule, otherwise it is harder to find the new Option rule on the rules list
		$I->click(['xpath' => '//a[contains(@href, "conf.php?del=14")]']);
		$I->wait(POPUP_DISPLAY_INTERVAL);
		$I->seeInPopup('Are you sure you want to delete Option rule number 14?');
		$I->acceptPopup();

		$I->selectOption('#category', 'Option');
		$I->click('Add');

		$I->selectOption('#type', 'block-policy');
		$I->click('Apply');

		$I->selectOption('#block-policy', 'drop');
		$I->fillField('#comment', 'Test');

		$I->checkOption('#forcesave');
		$I->click('Save');
	}

	protected function modifyRule(AcceptanceTester $I)
	{
		$I->selectOption('#block-policy', 'return');
		$this->clickApplySeeResult($I, 'set block-policy return # Test');

		$I->fillField('#comment', 'Test1');
		$this->clickApplySeeResult($I, $this->modifiedRule);
	}

	protected function revertModifications(AcceptanceTester $I)
	{
		$I->selectOption('#block-policy', 'drop');
		$this->clickApplySeeResult($I, 'set block-policy drop # Test1');

		$I->fillField('#comment', 'Test');
		$this->clickApplySeeResult($I, $this->revertedRule);
	}

	protected function modifyRuleQuick(AcceptanceTester $I)
	{
		$I->selectOption('#block-policy', 'return');
		$I->fillField('#comment', 'Test1');
		$I->click('Apply');
	}

	protected function revertModificationsQuick(AcceptanceTester $I)
	{
		$I->selectOption('#block-policy', 'drop');
		$I->fillField('#comment', 'Test');
		$I->click('Apply');
	}
}
?>