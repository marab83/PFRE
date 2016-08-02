<?php 
/* $pfre: Option.php,v 1.8 2016/08/02 09:54:29 soner Exp $ */

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

class Option extends Rule
{
	private $type= NULL;

	function __construct($str)
	{
		/// @todo Support set reassemble yes | no [no-df]
		/// @todo Support set state-defaults state-option, ...
		$this->keywords = array(
			'loginterface' => array(
				'method' => 'parseOption',
				'params' => array(),
				),
			'block-policy' => array(
				'method' => 'parseOption',
				'params' => array(),
				),
			'state-policy' => array(
				'method' => 'parseOption',
				'params' => array(),
				),
			'optimization' => array(
				'method' => 'parseOption',
				'params' => array(),
				),
			'ruleset-optimization' => array(
				'method' => 'parseOption',
				'params' => array(),
				),
			'debug' => array(
				'method' => 'parseOption',
				'params' => array(),
				),
			'hostid' => array(
				'method' => 'parseOption',
				'params' => array(),
				),
			'skip' => array(
				'method' => 'parseSkip',
				'params' => array(),
				),
			'fingerprints' => array(
				'method' => 'parseFingerprints',
				'params' => array(),
				),
			);

		parent::__construct($str);
	}

	function parseOption()
	{
		$this->rule['option'][$this->words[$this->index]]= $this->words[++$this->index];
	}

	function parseSkip()
	{
		list($this->rule['option']['skip'], $this->index)= $this->parseItem($this->words, ++$this->index);
	}

	function parseFingerprints()
	{
		// File name is in quotes, skip the quote
		list($this->rule['option']['fingerprints'], $this->index)= $this->parseString($this->words, $this->index);		
	}

	function generate()
	{
		$this->str= '';

		$this->genOption('block-policy');
		$this->genOption('debug');
		$this->genOption('fingerprints', '"', '"');
		$this->genOption('hostid');
		$this->genOption('loginterface');
		$this->genOption('optimization');
		$this->genOption('ruleset-optimization');
		$this->genOption('state-policy');
		$this->genSkip();
		
		$this->genComment();
		$this->str.= "\n";
		return $this->str;
	}

	function genOption($key, $head= '', $tail= '')
	{
		if (isset($this->rule['option'][$key])) {
			$this->str.= "set $key " . $head . preg_replace('/"/', '', $this->rule['option'][$key]) . $tail;
		}
	}

	function genSkip()
	{
		if (isset($this->rule['option']['skip'])) {
			if (!is_array($this->rule['option']['skip'])) {
				$this->genOption('skip', 'on ');
			} else {
				$this->str.= 'set skip on { ' . implode(' ', $this->rule['option']['skip']) . ' }';
			}
		}
	}

	function display($rulenumber, $count)
	{
		$this->dispHead($rulenumber);
		$this->dispOption();
		$this->dispTail($rulenumber, $count);
	}
	
	function dispOption()
	{
		?>
		<td title="Option" colspan="12">
			<?php
			$option= key($this->rule['option']);
			$value= $this->rule['option'][$option];
			if (in_array($option, array('loginterface', 'optimization', 'ruleset-optimization', 'block-policy', 'state-policy', 'debug', 'fingerprints', 'hostid'))) {
				echo "$option: $value";
			} elseif ($option == 'skip') {
				if (!is_array($value)) {
					echo "skip on $value";
				} else {
					foreach ($value as $skip) {
						echo "skip on $skip<br>";
					}
				}
			}
			?>
		</td>
		<?php
	}

	function input()
	{
		$this->inputKey('block-policy', 'option');
		$this->inputKey('optimization', 'option');
		$this->inputKey('ruleset-optimization', 'option');
		$this->inputKey('state-policy', 'option');
		$this->inputKey('fingerprints', 'option');
		$this->inputKey('hostid', 'option');
		$this->inputKey('loginterface', 'option');
		$this->inputKey('debug', 'option');
		$this->inputDel('skip', 'dropskip', 'option');
		$this->inputAdd('skip', 'addskip', 'option');

		$this->inputKey('comment');
		$this->inputDelEmpty(FALSE);
	}

	function edit($rulenumber, $modified, $testResult, $action)
	{
		$this->index= 0;
		$this->rulenumber= $rulenumber;

		$this->editHead($modified);

		if (filter_has_var(INPUT_POST, 'state')) {
			$this->type= filter_input(INPUT_POST, 'type');
		}

		if (isset($this->rule['option']) && count($this->rule['option'])) {
			$this->type= key($this->rule['option']);
		}

		if (!isset($this->type)) {
			$this->editSelectOption();
		}

		$this->editBlockPolicy();
		$this->editOptimization();
		$this->editRulesetOptimization();
		$this->editStatePolicy();
		$this->editFingerprints();
		$this->editHostid();
		$this->editLogInterface();
		$this->editDebug();
		$this->editSkip();

		if (isset($this->type)) {
			$this->editComment();
		}
		$this->editTail($modified, $testResult, $action);
	}

	function editSelectOption()
	{
		?>
		<tr class="oddline">
			<td class="title">
				<?php echo _TITLE('Select Option Type').':' ?>
			</td>
			<td>
				<select id="type" name="type">
					<option value="block-policy" <?php echo ($this->type == 'block-policy' ? 'selected' : ''); ?>>block-policy</option>
					<option value="optimization" <?php echo ($this->type == 'optimization' ? 'selected' : ''); ?>>optimization</option>
					<option value="ruleset-optimization" <?php echo ($this->type == 'ruleset-optimization' ? 'selected' : ''); ?>>ruleset-optimization</option>
					<option value="state-policy" <?php echo ($this->type == 'state-policy' ? 'selected' : ''); ?>>state-policy</option>
					<option value="fingerprints" <?php echo ($this->type == 'fingerprints' ? 'selected' : ''); ?>>fingerprints</option>
					<option value="hostid" <?php echo ($this->type == 'hostid' ? 'selected' : ''); ?>>hostid</option>
					<option value="loginterface" <?php echo ($this->type == 'loginterface' ? 'selected' : ''); ?>>loginterface</option>
					<option value="debug" <?php echo ($this->type == 'debug' ? 'selected' : ''); ?>>debug</option>
					<option value="skip" <?php echo ($this->type == 'skip' ? 'selected' : ''); ?>>skip</option>
				</select>
			</td>
		</tr>
		<?php
	}

	function editBlockPolicy()
	{
		if (isset($this->rule['option']['block-policy']) || $this->type == 'block-policy') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Block Policy').':' ?>
				</td>
				<td>
					<select id="block-policy" name="block-policy">
						<option value="drop" label="drop" <?php echo ($this->rule['option']['block-policy'] == 'drop' ? 'selected' : ''); ?>>drop</option>
						<option value="return" label="return" <?php echo ($this->rule['option']['block-policy'] == 'return' ? 'selected' : ''); ?>>return</option>
					</select>
					<?php $this->PrintHelp('block-policy') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editOptimization()
	{
		if (isset($this->rule['option']['optimization']) || $this->type == 'optimization') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Optimization').':' ?>
				</td>
				<td>
					<select id="optimization" name="optimization">
						<option value="normal" <?php echo ($this->rule['option']['optimization'] == 'normal' ? 'selected' : ''); ?>>normal</option>
						<option value="high-latency" <?php echo ($this->rule['option']['optimization'] == 'high-latency' ? 'selected' : ''); ?>>high-latency</option>
						<option value="satellite" <?php echo ($this->rule['option']['optimization'] == 'satellite' ? 'selected' : ''); ?>>satellite</option>
						<option value="aggressive" <?php echo ($this->rule['option']['optimization'] == 'aggressive' ? 'selected' : ''); ?>>aggressive</option>
						<option value="conservative" <?php echo ($this->rule['option']['optimization'] == 'conservative' ? 'selected' : ''); ?>>conservative</option>
					</select>
					<?php $this->PrintHelp('optimization') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editRulesetOptimization()
	{
		if (isset($this->rule['option']['ruleset-optimization']) || $this->type == 'ruleset-optimization') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Ruleset Optimization').':' ?>
				</td>
				<td>
					<select id="ruleset-optimization" name="ruleset-optimization">
						<option value="none" <?php echo ($this->rule['option']['ruleset-optimization'] == 'none' ? 'selected' : ''); ?>>none</option>
						<option value="basic" <?php echo ($this->rule['option']['ruleset-optimization'] == 'basic' ? 'selected' : ''); ?>>basic</option>
						<option value="profile" <?php echo ($this->rule['option']['ruleset-optimization'] == 'profile' ? 'selected' : ''); ?>>profile</option>
					</select>
					<?php $this->PrintHelp('ruleset-optimization') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editStatePolicy()
	{
		if (isset($this->rule['option']['state-policy']) || $this->type == 'state-policy') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('State Policy').':' ?>
				</td>
				<td>
					<select id="state-policy" name="state-policy">
						<option value="if-bound" <?php echo ($this->rule['option']['state-policy'] == 'if-bound' ? 'selected' : ''); ?>>if-bound</option>
						<option value="floating" <?php echo ($this->rule['option']['state-policy'] == 'floating' ? 'selected' : ''); ?>>floating</option>
					</select>
					<?php $this->PrintHelp('state-policy') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editFingerprints()
	{
		if (isset($this->rule['option']['fingerprints']) || $this->type == 'fingerprints') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Fingerprints File').':' ?>
				</td>
				<td>
					<input type="text" size="50" id="fingerprints" name="fingerprints" value="<?php echo $this->rule['option']['fingerprints']; ?>" placeholder="filename"/>
					<?php $this->PrintHelp('fingerprints') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editHostid()
	{
		if (isset($this->rule['option']['hostid']) || $this->type == 'hostid') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Host Id').':' ?>
				</td>
				<td>
					<input type="text" size="20" id="hostid" name="hostid" value="<?php echo $this->rule['option']['hostid']; ?>"  placeholder="number"/>
					<?php $this->PrintHelp('hostid') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editLogInterface()
	{
		if (isset($this->rule['option']['loginterface']) || $this->type == 'loginterface') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Log Interface').':' ?>
				</td>
				<td>
					<input type="text" size="10" id="loginterface" name="loginterface" value="<?php echo $this->rule['option']['loginterface']; ?>"  placeholder="interface"/>
					<?php $this->PrintHelp('loginterface') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editDebug()
	{
		if (isset($this->rule['option']['debug']) || $this->type == 'debug') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Debug').':' ?>
				</td>
				<td>
					<select id="debug" name="debug">
						<option value="emerg" <?php echo ($this->rule['option']['debug'] == 'emerg' ? 'selected' : ''); ?>>emerg</option>
						<option value="alert" <?php echo ($this->rule['option']['debug'] == 'alert' ? 'selected' : ''); ?>>alert</option>
						<option value="crit" <?php echo ($this->rule['option']['debug'] == 'crit' ? 'selected' : ''); ?>>crit</option>
						<option value="err" <?php echo ($this->rule['option']['debug'] == 'err' ? 'selected' : ''); ?>>err</option>
						<option value="warning" <?php echo ($this->rule['option']['debug'] == 'warning' ? 'selected' : ''); ?>>warning</option>
						<option value="notice" <?php echo ($this->rule['option']['debug'] == 'notice' ? 'selected' : ''); ?>>notice</option>
						<option value="info" <?php echo ($this->rule['option']['debug'] == 'info' ? 'selected' : ''); ?>>info</option>
						<option value="debug" <?php echo ($this->rule['option']['debug'] == 'debug' ? 'selected' : ''); ?>>debug</option>
					</select>
					<?php $this->PrintHelp('debug') ?>
				</td>
			</tr>
			<?php
		}
	}

	function editSkip()
	{
		if (isset($this->rule['option']['skip']) || $this->type == 'skip') {
			?>
			<tr class="<?php echo ($this->index++ % 2 ? 'evenline' : 'oddline'); ?>">
				<td class="title">
					<?php echo _TITLE('Skip Interfaces').':' ?>
				</td>
				<td>
					<?php
					$this->PrintDeleteLinks($this->rule['option']['skip'], 'dropskip');
					$this->PrintAddControls('addskip', NULL, 'if or macro', 40);
					$this->PrintHelp('skip');
					?>
				</td>
			</tr>
			<?php
		}
	}
}
?>