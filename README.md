# concrete5-auth0
Provides extra user authentication options for Concrete5 CMS via Auth0.com (in beta)

### Instructions:

1. Create an account with Auth0 and create a new application.

2. Extract this folder into your /application/ folder  
   (will eventually make this a package, but it's not as yet)

3. Add the following line to `/application/boostrap/autoload.php`:

        require(DIR_APPLICATION . '/vendor/autoload.php');

4. Add an entry to your database:

        INSERT INTO AuthenticationTypes VALUES (NULL, 'auth0', 'Auth0', 1, 5, 0);
 
5. A new authentication method will show up under Dashboard > Settings > Authentication.

6. Enable the 'Auth0' method and insert your domain, client ID and client secret as provided by Auth0.

7. Now, when logging into Concrete5, your login screen should show an Auth0 box.

This is not fully complete and could use more testing and other improvements, but appears to work OK so far. Contributions welcome via the normal Github pull request system.

### Known issues

The main known issue at the moment is that (sometimes?) when a user first logs in and is added to concrete5, they do not receive the permissions of their group until logging out and logging back in. There might be some code to be added later that can flush the permissions somehow.

### Contact

Contact @SimoEast on Twitter

### Licence

This code (excluding the 'vendor' folder) is copyright and published for research purposes only until a future release under an Open Source licence.
