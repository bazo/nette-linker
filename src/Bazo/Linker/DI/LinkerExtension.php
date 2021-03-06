<?php

namespace Bazo\Linker\DI;

/**
 * @author Martin Bažík <martin@bazo.sk>
 */
class LinkerExtension extends \Nette\DI\CompilerExtension
{

	/**
	 * Processes configuration data
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('linker'))
				->setFactory('Bazo\Linker\Linker');
	}


}
