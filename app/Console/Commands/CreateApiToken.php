<?php

namespace App\\Console\\Commands;

use Illuminate\\Console\\Command;
use App\\Models\\User;
use Illuminate\\Support\\Facades\\Hash;

class CreateApiToken extends Command
{
    protected \ = " api:create-token -encodedCommand ZQBtAGEAaQBsAD0AYQBkAG0AaQBuAEAAcwBpAHQAZQBhAGMAYwBlAHMAcwAuAHIAdQA= \;
 protected \ = \Create API token for user\;

 public function handle()
 {
 \ = \->argument(\email\);
 \ = User::firstOrCreate(
 [\email\ => \],
 [
ame\ => \Admin\, \password\ => Hash::make(	emp_password_123\)]
 );
 
 \ = \->createToken(pi-token\)->plainTextToken;
 
 \->info(\API Token created successfully!\);
 \->line(\Token: \ . \);
 
 return 0;
 }
}
