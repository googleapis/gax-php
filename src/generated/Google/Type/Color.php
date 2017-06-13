<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: google/type/color.proto

namespace Google\Type;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * <pre>
 * Represents a color in the RGBA color space. This representation is designed
 * for simplicity of conversion to/from color representations in various
 * languages over compactness; for example, the fields of this representation
 * can be trivially provided to the constructor of "java.awt.Color" in Java; it
 * can also be trivially provided to UIColor's "+colorWithRed:green:blue:alpha"
 * method in iOS; and, with just a little work, it can be easily formatted into
 * a CSS "rgba()" string in JavaScript, as well. Here are some examples:
 * Example (Java):
 *      import com.google.type.Color;
 *      // ...
 *      public static java.awt.Color fromProto(Color protocolor) {
 *        float alpha = protocolor.hasAlpha()
 *            ? protocolor.getAlpha().getValue()
 *            : 1.0;
 *        return new java.awt.Color(
 *            protocolor.getRed(),
 *            protocolor.getGreen(),
 *            protocolor.getBlue(),
 *            alpha);
 *      }
 *      public static Color toProto(java.awt.Color color) {
 *        float red = (float) color.getRed();
 *        float green = (float) color.getGreen();
 *        float blue = (float) color.getBlue();
 *        float denominator = 255.0;
 *        Color.Builder resultBuilder =
 *            Color
 *                .newBuilder()
 *                .setRed(red / denominator)
 *                .setGreen(green / denominator)
 *                .setBlue(blue / denominator);
 *        int alpha = color.getAlpha();
 *        if (alpha != 255) {
 *          result.setAlpha(
 *              FloatValue
 *                  .newBuilder()
 *                  .setValue(((float) alpha) / denominator)
 *                  .build());
 *        }
 *        return resultBuilder.build();
 *      }
 *      // ...
 * Example (iOS / Obj-C):
 *      // ...
 *      static UIColor* fromProto(Color* protocolor) {
 *         float red = [protocolor red];
 *         float green = [protocolor green];
 *         float blue = [protocolor blue];
 *         FloatValue* alpha_wrapper = [protocolor alpha];
 *         float alpha = 1.0;
 *         if (alpha_wrapper != nil) {
 *           alpha = [alpha_wrapper value];
 *         }
 *         return [UIColor colorWithRed:red green:green blue:blue alpha:alpha];
 *      }
 *      static Color* toProto(UIColor* color) {
 *          CGFloat red, green, blue, alpha;
 *          if (![color getRed:&amp;red green:&amp;green blue:&amp;blue alpha:&amp;alpha]) {
 *            return nil;
 *          }
 *          Color* result = [Color alloc] init];
 *          [result setRed:red];
 *          [result setGreen:green];
 *          [result setBlue:blue];
 *          if (alpha &lt;= 0.9999) {
 *            [result setAlpha:floatWrapperWithValue(alpha)];
 *          }
 *          [result autorelease];
 *          return result;
 *     }
 *     // ...
 *  Example (JavaScript):
 *     // ...
 *     var protoToCssColor = function(rgb_color) {
 *        var redFrac = rgb_color.red || 0.0;
 *        var greenFrac = rgb_color.green || 0.0;
 *        var blueFrac = rgb_color.blue || 0.0;
 *        var red = Math.floor(redFrac * 255);
 *        var green = Math.floor(greenFrac * 255);
 *        var blue = Math.floor(blueFrac * 255);
 *        if (!('alpha' in rgb_color)) {
 *           return rgbToCssColor_(red, green, blue);
 *        }
 *        var alphaFrac = rgb_color.alpha.value || 0.0;
 *        var rgbParams = [red, green, blue].join(',');
 *        return ['rgba(', rgbParams, ',', alphaFrac, ')'].join('');
 *     };
 *     var rgbToCssColor_ = function(red, green, blue) {
 *       var rgbNumber = new Number((red &lt;&lt; 16) | (green &lt;&lt; 8) | blue);
 *       var hexString = rgbNumber.toString(16);
 *       var missingZeros = 6 - hexString.length;
 *       var resultBuilder = ['#'];
 *       for (var i = 0; i &lt; missingZeros; i++) {
 *          resultBuilder.push('0');
 *       }
 *       resultBuilder.push(hexString);
 *       return resultBuilder.join('');
 *     };
 *     // ...
 * </pre>
 *
 * Protobuf type <code>google.type.Color</code>
 */
class Color extends \Google\Protobuf\Internal\Message
{
    /**
     * <pre>
     * The amount of red in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float red = 1;</code>
     */
    private $red = 0.0;
    /**
     * <pre>
     * The amount of green in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float green = 2;</code>
     */
    private $green = 0.0;
    /**
     * <pre>
     * The amount of blue in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float blue = 3;</code>
     */
    private $blue = 0.0;
    /**
     * <pre>
     * The fraction of this color that should be applied to the pixel. That is,
     * the final pixel color is defined by the equation:
     *   pixel color = alpha * (this color) + (1.0 - alpha) * (background color)
     * This means that a value of 1.0 corresponds to a solid color, whereas
     * a value of 0.0 corresponds to a completely transparent color. This
     * uses a wrapper message rather than a simple float scalar so that it is
     * possible to distinguish between a default value and the value being unset.
     * If omitted, this color object is to be rendered as a solid color
     * (as if the alpha value had been explicitly given with a value of 1.0).
     * </pre>
     *
     * <code>.google.protobuf.FloatValue alpha = 4;</code>
     */
    private $alpha = null;

    public function __construct() {
        \GPBMetadata\Google\Type\Color::initOnce();
        parent::__construct();
    }

    /**
     * <pre>
     * The amount of red in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float red = 1;</code>
     */
    public function getRed()
    {
        return $this->red;
    }

    /**
     * <pre>
     * The amount of red in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float red = 1;</code>
     */
    public function setRed($var)
    {
        GPBUtil::checkFloat($var);
        $this->red = $var;

        return $this;
    }

    /**
     * <pre>
     * The amount of green in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float green = 2;</code>
     */
    public function getGreen()
    {
        return $this->green;
    }

    /**
     * <pre>
     * The amount of green in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float green = 2;</code>
     */
    public function setGreen($var)
    {
        GPBUtil::checkFloat($var);
        $this->green = $var;

        return $this;
    }

    /**
     * <pre>
     * The amount of blue in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float blue = 3;</code>
     */
    public function getBlue()
    {
        return $this->blue;
    }

    /**
     * <pre>
     * The amount of blue in the color as a value in the interval [0, 1].
     * </pre>
     *
     * <code>float blue = 3;</code>
     */
    public function setBlue($var)
    {
        GPBUtil::checkFloat($var);
        $this->blue = $var;

        return $this;
    }

    /**
     * <pre>
     * The fraction of this color that should be applied to the pixel. That is,
     * the final pixel color is defined by the equation:
     *   pixel color = alpha * (this color) + (1.0 - alpha) * (background color)
     * This means that a value of 1.0 corresponds to a solid color, whereas
     * a value of 0.0 corresponds to a completely transparent color. This
     * uses a wrapper message rather than a simple float scalar so that it is
     * possible to distinguish between a default value and the value being unset.
     * If omitted, this color object is to be rendered as a solid color
     * (as if the alpha value had been explicitly given with a value of 1.0).
     * </pre>
     *
     * <code>.google.protobuf.FloatValue alpha = 4;</code>
     */
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * <pre>
     * The fraction of this color that should be applied to the pixel. That is,
     * the final pixel color is defined by the equation:
     *   pixel color = alpha * (this color) + (1.0 - alpha) * (background color)
     * This means that a value of 1.0 corresponds to a solid color, whereas
     * a value of 0.0 corresponds to a completely transparent color. This
     * uses a wrapper message rather than a simple float scalar so that it is
     * possible to distinguish between a default value and the value being unset.
     * If omitted, this color object is to be rendered as a solid color
     * (as if the alpha value had been explicitly given with a value of 1.0).
     * </pre>
     *
     * <code>.google.protobuf.FloatValue alpha = 4;</code>
     */
    public function setAlpha(&$var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\FloatValue::class);
        $this->alpha = $var;

        return $this;
    }

}

