# REQUIRE ----------------------------------------------------------------------
# Require any additional compass plugins here.

# require 'compass/import-once/activate'
# require 'breakpoint'
# require 'susy'

# PROJECT PATHS ---------------------------------------------------------------

http_path = "/skin/frontend/atena/default/src"
css_dir = "../css"
sass_dir = "scss"
images_dir = "../images"
javascripts_dir = "../js"
relative_assets = true

# FRAMEWORKS -------------------------------------------------------------------
# Additional path

add_import_path "../../../rwd/default/scss"

# SPRITES & IMAGES -------------------------------------------------------------
# To enable relative paths to assets via compass helper functions. Uncomment:

relative_assets = true

# OUTPUT -----------------------------------------------------------------------
# You can select your preferred output style here (can be overridden via the command line):

output_style = :expanded # :expanded or :nested or :compact or :compressed
environment = :development # :production or :development

# SASS -------------------------------------------------------------------------
# To disable debugging comments that display the original location of your selectors. Uncomment:

line_comments = false # true or false
cache = true # true or false
color_output = false # required for mixture
