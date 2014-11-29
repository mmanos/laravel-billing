<?php namespace Mmanos\Billing;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class CustomerTableCommand extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'laravel-billing:customer-table';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create a migration for the laravel-billing customer table columns';
	
	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$full_path = $this->createBaseMigration();
		
		file_put_contents($full_path, $this->getMigrationStub());
		
		$this->info('Migration created successfully!');
		
		$this->call('dump-autoload');
	}
	
	/**
	 * Create a base migration file for the customers.
	 *
	 * @return string
	 */
	protected function createBaseMigration()
	{
		$name = 'add_customer_billing_columns_to_' . $this->argument('table');
		
		$path = $this->laravel['path'].'/database/migrations';
		
		return $this->laravel['migration.creator']->create($name, $path);
	}
	
	/**
	 * Get the contents of the customer migration stub.
	 *
	 * @return string
	 */
	protected function getMigrationStub()
	{
		$stub = file_get_contents(__DIR__.'/../Mmanos/Billing/Stubs/CustomerMigration.stub');
		
		$stub = str_replace('customer_table', $this->argument('table'), $stub);
		$stub = str_replace(
			'AddCustomerBillingColumnsTo',
			'AddCustomerBillingColumnsTo' . Str::studly($this->argument('table')),
			$stub
		);
		
		return $stub;
	}
	
	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('table', InputArgument::REQUIRED, 'The name of your customer billable table.'),
		);
	}
}
