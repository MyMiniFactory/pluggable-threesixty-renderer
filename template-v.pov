
#include "math.inc"
#include "finish.inc"
#include "transforms.inc"
background {color rgb 1}

#declare Min_ext = min_extent(m_body);
#declare Max_ext = max_extent(m_body);

#macro parsePositive(Value)
  #if (Value < 0)
    #local Value = Value* -1;
  #end
  Value
#end

#declare X_len = Max_ext.x - Min_ext.x;
#declare Y_len = Max_ext.y - Min_ext.y;
#declare Z_len = Max_ext.z - Min_ext.z;


#declare Radius_offset = abs(max(X_len, Y_len, Z_len) * tan( 45.0 / 2) / 2);
#declare Radius = max(abs((X_len + Radius_offset) / tan( 45.0 / 2)), abs((Y_len + Radius_offset) / tan( 45.0 / 2)), abs((Z_len + Radius_offset) / tan( 45.0 / 2)));
#declare Theta = - pi / 4;
#declare Phi = - pi / 4 + 0;

#declare X_offset = Min_ext.x + X_len / 2;
#declare Y_offset = Min_ext.y + Y_len / 2;
#declare Z_offset = Min_ext.z + Z_len / 2;

#declare X_pos = Radius * sin(Theta) * cos(Phi) + X_offset;

// #warning str(X_len, 0 ,0)
// #warning str(Y_len, 0 ,0)
// #warning str(Z_len, 0 ,0)

// #warning str(Max_ext.x, 0 ,0)
// #warning str(Max_ext.y, 0 ,0)
// #warning str(Max_ext.z, 0 ,0)
// #warning str(Min_ext.x, 0 ,0)
// #warning str(Min_ext.y, 0 ,0)
// #warning str(Min_ext.z, 0 ,0)

#if (( parsePositive(X_len) > parsePositive(Y_len) ) & (parsePositive(X_len) > parsePositive(Z_len)))
  #declare Y_pos = parsePositive(X_len);
  // #warning "Using X_len"

#elseif ((parsePositive(Y_len) > parsePositive(X_len) ) & (parsePositive(Y_len) > parsePositive(Z_len)))
  #declare Y_pos = parsePositive(Y_len);
  // #warning "Using Y_len"

#else
  #declare Y_pos = parsePositive(Z_len);
  // #warning "Using Z_len"
#end

#declare Z_pos = Radius * cos(Theta) + Z_offset;


light_source {
  <0, ((Y_offset + Y_pos*1.5)* -1), Z_offset*1.5>
  rgb 1
  parallel
  point_at <X_offset,Y_offset,Z_offset>
}
global_settings {
  assumed_gamma 2
  ambient_light rgb <0.1,0.1,0.1>
}

camera {
  perspective
  right x
  up y
  location <0, ((Y_offset + Y_pos*1.5)* -1), Z_offset>
  sky <0, 0, 1>
  look_at <X_offset, Y_offset, Z_offset>
}
sky_sphere {
  pigment {
  gradient y
  color_map {
    [0.0 rgb <1.0,1.0,1.0>] //153, 178.5, 255 //150, 240, 192
    [0.7 rgb <0.9,0.9,0.9>] // 0, 25.5, 204 //155, 240, 96
  }
  scale 2
  translate 1
  }
}

object {
  m_body
  texture {
    pigment {color <1,1,1>}
    finish {phong 0.05}
  }
  Rotate_Around_Trans(<360*clock, 0, 0>, <X_offset, Y_offset, Z_offset>)
}