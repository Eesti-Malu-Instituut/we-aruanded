# Eesti Mälu Instituudi aruanded

See on Eesti Mälu Instituudi andmebaasi statistika repositoorium.

## Nõuded süsteemile

- PHP versioon 8.0 või hilisem
    `apt install php libapache2-mod-php`
- ssh2 for PHP
    `apt install php-ssh2`


## Installeerimine

```sh
# Clone the repository
git clone git@github.com:Eesti-Malu-Instituut/we-aruanded.git
# Kui ssh ühendust ei soovi tekitada, siis saab ka
git clone https://github.com/Eesti-Malu-Instituut/we-aruanded.git html

# Navigate to the project's directory
cd we-aruanded

# Generate SSH key for ssh2_connect
ssh-keygen -t rsa -m PEM

# Copy project environment file
cp -n .env.example .env

# Fill project environment file with correct data
vim .env
:x

# copy files to web directory
# (local directory would be /var/www/html)
cp -R -n * /var/www/html

# open your browser at `http://localhost:8000/index.php`

# filters can be added and mysql queries modified in filters.json file

Parameters:

- sql: base sql query for each filter. select, from etc are parts of the query
- columns: default table columns
- filters: array of filters available. Filter parameters:
  - filter group, contains an key (ex "occurrences"), parameters ("data") and an array of filters
    parameters:
    - header: group label
    - columns: array of column keys > labels. available column keys are "repressed", "arrested" and "deported"
      array of filters:
    - occurrence: year from which the age is calculated. If many filters are chosen, the earliest year prevails
    - type: filter type. Available options are "checkbox" and "input"
    - label: filter label
    - disabled: if "true" then filter cannot be selected
    - visible: if "false" then filter is not visible. Default is "true"
    - sql: array of query parts:
      - join: all the JOIN querys and conditions
      - where: string or array of WHERE clauses. By default, when combining different filters then WHERE clauses are joined by AND. If "where" parameter is array,
        then the array key defines the group, where the clauses are joined by OR.
      - extra_sql: array of full SQL query to populate column shown in array key

# table rows can be modified in table_rows.json file
```

## Local setup with DDEV

### Requirements
* DDEV

### setup
```sh
# Clone the repository
git clone git@github.com:Eesti-Malu-Instituut/we-aruanded.git

# Navigate to the project's directory
cd we-aruanded

# Start DDEV container
ddev start

# ssh into DDEV container
ddev ssh

# Generate SSH key for ssh2_connect
ssh-keygen -t rsa -m PEM

# Add SSH public key to server ssh user authorized_keys

# Copy project environment file
cp -n .env.example .env

# Fill project environment file with correct data
nano .env

# open your browser at `https://eesti-malu-instituut-aruanded.ddev.site`
```