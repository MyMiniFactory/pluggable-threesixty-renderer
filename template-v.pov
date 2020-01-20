#include "math.inc"
#include "finish.inc"
#include "transforms.inc"
background {color rgb 1}

#macro parsePositive(Value)
  #if (Value < 0)
    #local Value = Value* -1;
  #end
  Value
#end

#declare Min_ext = min_extent(m_body);
#declare Max_ext = max_extent(m_body);

#declare X_len = Max_ext.x - Min_ext.x;
#declare Y_len = Max_ext.y - Min_ext.y;
#declare Z_len = Max_ext.z - Min_ext.z;


#declare Radius_offset = abs(max(X_len, Y_len, Z_len) * tan( 45.0 / 2) / 2);
#declare Radius = max(abs((X_len + Radius_offset) / tan( 45.0 / 2)), abs((Y_len + Radius_offset) / tan( 45.0 / 2)), abs((Z_len + Radius_offset) / tan( 45.0 / 2)));
#declare Theta = - pi / 4;
#declare Phi = - pi / 4 + 0;

#declare X_offset = 0;
#declare Y_offset = 0;
#declare Z_offset = 0;

#declare X_pos = Radius * sin(Theta) * cos(Phi) + X_offset;

#if (( parsePositive(X_len) > parsePositive(Y_len) ) & (parsePositive(X_len) > parsePositive(Z_len)))
  #declare Y_pos = parsePositive(X_len);
#elseif ((parsePositive(Y_len) > parsePositive(X_len) ) & (parsePositive(Y_len) > parsePositive(Z_len)))
  #declare Y_pos = parsePositive(Y_len);
#else
  #declare Y_pos = parsePositive(Z_len);
#end

#declare Z_pos = Radius * cos(Theta) + Z_offset;


light_source {
  <0, ((Y_pos*1.5)* -1), Z_pos*1.5>
  rgb 1
  parallel
  point_at <X_offset,Y_offset,Z_offset>
}
global_settings {
  assumed_gamma 2
  ambient_light rgb 1
}

camera {
  perspective
  right x
  up y
  location <0,(Y_pos*0.25)* -1, Z_pos>
  sky <0, 0, 1>
  look_at <X_offset, Y_offset, Z_offset>
}
background { colour rgbt <1,1,1,1> }

object {
  m_body
  texture {
    pigment {color <1,1,1>}
    finish { ambient 0.2 diffuse 0.7 }
  }
  Center_Trans(m_body, x)
  Center_Trans(m_body, y)
  Center_Trans(m_body, z)
  scale <-1,1,1>
  Rotate_Around_Trans(<360*clock, 0, 0>, <0, 0, 0>)
}
