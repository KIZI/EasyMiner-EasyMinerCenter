GH_PAGES_REPO=$(echo $TRAVIS_REPO_SLUG | cut -d / -f1)
git clone -b gh-pages --single-branch https://github.com/$GH_PAGES_REPO/EasyMiner-WebUITestsResults.git gh-pages
cd gh-pages
./export-test-results.sh
./update-links.sh
./update-index-page.sh
git add .
git -c user.name='TravisCI' -c user.email='travis' commit -m "Autoupdate test result for $TRAVIS_BRANCH-$TRAVIS_COMMIT-$TRAVIS_COMMIT_MESSAGE"
git remote set-url origin git@github.com:$GH_PAGES_REPO/EasyMiner-WebUITestsResults.git
git push
