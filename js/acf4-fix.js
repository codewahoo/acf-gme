//workaround blocking ACF ver 4 from loading google maps twice
if (google) {
  google.load = function(p1,p2,p3) {
    if(p3 !== 'undefined' && p3.callback) p3.callback();
  }
}